<?php

namespace Drupal\search_api_attachments\Plugin\SearchApiAttachmentsTextExtractor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api_attachments\TextExtractorPluginBase;

/**
 * @SearchApiAttachmentsTextExtractor(
 *   id = "tika_extractor",
 *   label = @Translation("Tika Extractor"),
 *   description = @Translation("Adds Tika extractor support."),
 * )
 */
class TikaExtractor extends TextExtractorPluginBase {

  /**
   * Extract file with Tika library.
   *
   * @param $file
   *   A file object.
   *
   * @return string
   *   The text extracted from the file.
   *
   * @throws \Exception
   */
  public function extract($file) {
    $filepath = $this->getRealpath($file->getFileUri());
    $tika = realpath($this->configuration['tika_path']);
    // UTF-8 multibyte characters will be stripped by escapeshellargs() for the
    // default C-locale.
    // So temporarily set the locale to UTF-8 so that the filepath remains valid.
    $backup_locale = setlocale(LC_CTYPE, '0');
    setlocale(LC_CTYPE, 'en_US.UTF-8');
    $param = '';
    if ($file->getMimeType() != 'audio/mpeg') {
      $param = ' -Dfile.encoding=UTF8 -cp ' . escapeshellarg($tika);
    }

    // Force running the Tika jar headless.
    $param = ' -Djava.awt.headless=true ' . $param;

    $cmd = escapeshellcmd('java') . $param . ' -jar ' . escapeshellarg($tika) . ' -t ' . escapeshellarg($filepath);
    if (strpos(ini_get('extension_dir'), 'MAMP/')) {
      $cmd = 'export DYLD_LIBRARY_PATH=""; ' . $cmd;
    }
    // Restore the locale.
    setlocale(LC_CTYPE, $backup_locale);
    // Support UTF-8 commands: http://www.php.net/manual/en/function.shell-exec.php#85095
    shell_exec("LANG=en_US.utf-8");
    return shell_exec($cmd);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['tika_path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Path to Tika .jar file'),
      '#description' => $this->t('Enter the full path to tika executable jar file. For example: "/var/apache-tika/tika-app-1.7.jar".'),
      '#default_value' => $this->configuration['tika_path'],
    );
    //@todo test connection live
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (isset($values['text_extractor_config']['tika_path']) && !file_exists($values['text_extractor_config']['tika_path'])) {
      $form_state->setError($form['text_extractor_config']['tika_path'], $this->t('Invalid path or filename for tika application jar.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['tika_path'] = $form_state->getValue(array('text_extractor_config', 'tika_path'));
    parent::submitConfigurationForm($form, $form_state);
  }

}
