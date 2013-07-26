<?php

/**
 * @file
 * Search API data alteration callback
 */

class SearchApiAttachmentsAlterSettings extends SearchApiAbstractAlterCallback {

  public function alterItems(array &$items) {

    $exclude = array();
    foreach (explode(' ', $this->options['excluded_extensions']) as $ext) {
      $exclude[$ext] = file_get_mimetype('dummy.' . $ext);
    }
    if ($this->index->item_type == 'file') {
      foreach ($items as &$item) {
        $file = array();
        foreach ($item as $key => $value) {
          $file[$key] = $value;
        }
        if (!in_array($file['filemime'], $exclude) && !self::is_temporary($file)) {
          $item->attachments_content = self::getFileContent($file);
        }
      }
    }
    else {
      $fields = self::getFileFields();
      foreach ($items as $id => &$item) {
        foreach ($fields as $name => $field) {
          if (isset($item->$name)) {
            foreach ($item->$name as $value) {
              foreach ($value as $file) {
                if (!in_array($file['filemime'], $exclude) && !self::is_temporary($file)) {
                  $attachments = 'attachments_' . $name;
                  $item->$attachments .= self::getFileContent($file) . ' ';
                }
              }
            }
          }
        }
      }
    }
  }

  public function configurationForm() {
    $default = implode(' ', search_api_attachments_default_excluded());
    $form['excluded_extensions'] = array(
      '#type' => 'textfield',
      '#title' => t('Excluded file extensions'),
      '#default_value' => isset($this->options['excluded_extensions']) ? $this->options['excluded_extensions'] : $default,
      '#size' => 80,
      '#maxlength' => 255,
      '#description' => t('File extensions that are excluded from indexing. Separate extensions with a space and do not include the leading dot. Extensions are internally mapped to a MIME type, so it is not necessary to put variations that map to the same type (e.g. tif is sufficient for tif and tiff)'),
    );
    return $form;
  }

  public function propertyInfo() {
    $ret = array();
    if ($this->index->item_type == 'file') {
      $ret['attachments_content'] = array(
        'label' => 'File content',
        'description' => 'File content',
        'type' => 'text',
      );
    }
    else {
      $fields = self::getFileFields();
      foreach ($fields as $name => $field) {
        $ret['attachments_' . $name] = array(
          'label' => 'Attachment content: ' . $name,
          'description' => $name,
          'type' => 'text',
        );
      }
    }
    return $ret;
  }

  protected function getFileFields() {
    $ret = array();
    foreach (field_info_fields() as $name => $field) {
      if ($field['type'] == 'file' && array_key_exists($this->index->item_type, $field['bundles'])) {
        $ret[$name] = $field;
      }
    }
    return $ret;
  }

  protected function getFileContent($file) {
    $ret = FALSE;
    if (file_exists($file['uri'])) {
      if ($file['filemime'] == 'text/plain' || $file['filemime'] == 'text/x-diff') {
        $ret = self::extract_simple($file);
      }
      elseif (in_array($file['filemime'], array('image/jpeg', 'image/jpg', 'image/tiff'))) {
        $ret = self::extract_exif($file);
      }
      else {
        $ret = self::extract_tika($file);
      }
    }
    else {
      // Log the missing file information.
      watchdog('search_api_attachments', "Couldn't index %filename content because this file was missing.", array('%filename' => $file['filename']));
    }

    return $ret;
  }

  protected function extract_simple($file) {
    $text = file_get_contents(self::get_realpath($file));
    $text = iconv("UTF-8", "UTF-8//IGNORE", $text);
    $text = filter_xss(str_replace(array('<', '>'), array(' <', '> '), $text), array());
    $text = htmlspecialchars(html_entity_decode($text, ENT_NOQUOTES, 'UTF-8'), ENT_NOQUOTES, 'UTF-8');
    $text = trim($text);
    return $text;
  }

  protected function extract_exif($file) {
    $ret = '';
    $size = getimagesize(self::get_realpath($file), $info);
    if (isset($info['APP13'])) {
      $iptc = iptcparse($info['APP13']);
      foreach ($iptc as $key => $value) {
        foreach ($value as $innerkey => $innervalue) {
          $ret .= $innervalue . ' ';
        }
      }
    }
    return $ret;
  }

  protected function extract_tika($file) {
    $filepath = self::get_realpath($file);
    $tika_path = realpath(variable_get('search_api_attachments_tika_path', ''));
    $tika = realpath($tika_path . '/' . variable_get('search_api_attachments_tika_jar', 'tika-app-1.4.jar'));
    if (!($tika) || !is_file($tika)) {
      throw new Exception(t('Invalid path or filename for tika application jar.'));
    }
    // UTF-8 multibyte characters will be stripped by escapeshellargs() for the 
    // default C-locale.
    // So temporarily set the locale to UTF-8 so that the filepath remains valid.
    $backup_locale = setlocale(LC_CTYPE, '0');
    setlocale(LC_CTYPE, 'en_US.UTF-8');
    $param = '';
    if ($file['filemime'] != 'audio/mpeg') {
      $param = ' -Dfile.encoding=UTF8 -cp ' . escapeshellarg($tika_path);
    }
    $cmd = escapeshellcmd('java') . $param . ' -jar ' . escapeshellarg($tika) . ' -t ' . escapeshellarg($filepath);
    if (strpos(ini_get('extension_dir'), 'MAMP/')) {
      $cmd = 'export DYLD_LIBRARY_PATH=""; ' . $cmd;
    }
    // Support UTF-8 commands: http://www.php.net/manual/pt_BR/function.shell-exec.php#85095
    $cmd = "LANG=en_US.utf-8; $cmd";
    // Restore the locale.
    setlocale(LC_CTYPE, $backup_locale);
    return shell_exec($cmd);
  }
  
  protected function is_temporary($file) {
    $needle = 'temporary://';
    $haystack = $file['uri'];
    $test = strpos($haystack, $needle);
    $result = ($test !== FALSE);
    return $result;
  }

  protected function get_realpath($file) {
    $wrapper = file_stream_wrapper_get_instance_by_uri($file['uri']);
    $scheme = file_uri_scheme($file['uri']);
    $local_wrappers = file_get_stream_wrappers(STREAM_WRAPPERS_LOCAL);
    if (in_array($scheme, array_keys($local_wrappers))) {
      drupal_debug($wrapper->realpath());
      return $wrapper->realpath();
    }
    else {
      return $wrapper->getExternalUrl();
    }
  }
}
