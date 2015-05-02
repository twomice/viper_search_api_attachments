<?php

namespace Drupal\search_api_attachments\Form;

use Drupal\Component\Utility\String;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api_attachments\TextExtractorPluginBase;
use Drupal\search_api_attachments\TextExtractorPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form.
 */
class TextExtractorFormSettings extends ConfigFormBase {

  /**
   * Name of the config being edited.
   */
  const CONFIGNAME = 'search_api_attachments.admin_config';

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, TextExtractorPluginManager $textExtractorPluginManager) {
    parent::__construct($config_factory);
    $this->textExtractorPluginManager = $textExtractorPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('config.factory'), $container->get('plugin.manager.search_api_attachments.text_extractor')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array(static::CONFIGNAME);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'search_api_attachments_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::CONFIGNAME);
    $form['extraction_method'] = array(
      '#type' => 'select',
      '#title' => $this->t('Extraction method'),
      '#description' => $this->t('Select the extraction method you want to use.'),
      '#empty_value' => '',
      '#options' => $this->getExtractionPluginInformations()['labels'],
      '#default_value' => $config->get('extraction_method'),
      '#required' => TRUE,
      '#ajax' => array(
        'callback' => array(get_class($this), 'buildAjaxTextExtractorConfigForm'),
        'wrapper' => 'search-api-attachments-extractor-config-form',
        'method' => 'replace',
        'effect' => 'fade',
      ),
    );

    $this->buildTextExtractorConfigForm($form, $form_state);
    $trigger = $form_state->getTriggeringElement();
    if (!empty($trigger['#is_button'])) {
      $this->buildTextExtractorTestResultForm($form, $form_state);
    }
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit and test extraction'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(static::CONFIGNAME);
    // if it is from the configuration
    $extractor_plugin_id = $form_state->getValue('extraction_method');
    if ($extractor_plugin_id) {
      $configuration = $config->get($extractor_plugin_id . '_configuration');
      $extractor_plugin = $this->textExtractorPluginManager->createInstance($extractor_plugin_id, $configuration);
      $extractor_plugin->validateConfigurationForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(static::CONFIGNAME);
    // it is due to the ajax.
    $extractor_plugin_id = $form_state->getValue('extraction_method');

    if ($extractor_plugin_id) {
      $configuration = $config->get($extractor_plugin_id . '_configuration');
      $extractor_plugin = $this->textExtractorPluginManager->createInstance($extractor_plugin_id, $configuration);
      $extractor_plugin->submitConfigurationForm($form, $form_state);
    }
    $config = \Drupal::configFactory()
        ->getEditable(static::CONFIGNAME);
    $config->set('extraction_method', $extractor_plugin_id);
    $config->save();
    $account = \Drupal::currentUser();
    $contents = $this->t('Congratulations, the extraction seems working !');
    $filepath = 'public://search_api_attachments_test_extraction.txt';
    file_put_contents($filepath, $contents);

    $file = entity_create('file', array(
      'uri' => $filepath,
      'uid' => $account->id(),
    ));
    $file->save();
    $extracted_data = $extractor_plugin->extract($file);
    if ($extracted_data == '') {
      $data = $this->t("Unfortunately, the extraction doesn't seem to work with this configuration !");
    }
    else {
      $data = $this->t('Extracted data: %extracted_data', array('%extracted_data' => $extracted_data));
    }
    $storage = array(
      'extracted_test_text' => $data,
    );
    $file->delete();
    $form_state->setStorage($storage);
    $form_state->setRebuild();
  }

  /**
   * Get definition of Extraction plugins from their annotation definition.
   *
   * @return array
   *   Array with 'labels' and 'descriptions' as keys contaigning plugin ids
   *   and their labels or descriptions.
   */
  public function getExtractionPluginInformations() {
    $options = array(
      'labels' => array(),
      'descriptions' => array()
    );
    foreach ($this->textExtractorPluginManager->getDefinitions() as $plugin_id => $plugin_definition) {
      $options['labels'][$plugin_id] = String::checkPlain($plugin_definition['label']);
      $options['descriptions'][$plugin_id] = String::checkPlain($plugin_definition['description']);
    }
    return $options;
  }

  /**
   * Subform that will be updated with Ajax to display the configuration of an
   * extraction plugin method.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function buildTextExtractorConfigForm(array &$form, FormStateInterface $form_state) {
    $form['text_extractor_config'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'id' => 'search-api-attachments-extractor-config-form',
      ),
      '#tree' => TRUE,
    );
    $config = $this->config(static::CONFIGNAME);
    if ($form_state->getValue('extraction_method') != '') {
      // it is due to the ajax.
      $extractor_plugin_id = $form_state->getValue('extraction_method');
    }
    else {
      $extractor_plugin_id = $config->get('extraction_method');
      $ajax_submitted_empty_value = $form_state->getValue('form_id');
    }
    $form['text_extractor_config']['#type'] = 'details';
    $form['text_extractor_config']['#title'] = $this->t('Configure extractor %plugin', array('%plugin' => $this->getExtractionPluginInformations()['labels'][$extractor_plugin_id]));
    $form['text_extractor_config']['#description'] = $this->getExtractionPluginInformations()['descriptions'][$extractor_plugin_id];
    $form['text_extractor_config']['#open'] = TRUE;
    // If the form is submitted with ajax and the empty value chosed or if there
    // is no configuration yet and no extraction method was chosen in the form.
    if ($ajax_submitted_empty_value || !$extractor_plugin_id) {
      $form['text_extractor_config']['#title'] = $this->t('Please make a choice');
      $form['text_extractor_config']['#description'] = $this->t('Please choose an extraction method in the list above.');
    }

    if ($extractor_plugin_id && !$ajax_submitted_empty_value) {
      $configuration = $config->get($extractor_plugin_id . '_configuration');
      $extractor_plugin = $this->textExtractorPluginManager->createInstance($extractor_plugin_id, $configuration);
      $text_extractor_form = $extractor_plugin->buildConfigurationForm();

      $form['text_extractor_config'] += $text_extractor_form;
    }
  }

  /**
   * Subform to test the configuration of an
   * extraction plugin method.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function buildTextExtractorTestResultForm(array &$form, FormStateInterface $form_state) {
    if (isset($form['text_extractor_config'])) {
      $form['text_extractor_config']['test_result']['#type'] = 'details';
      $form['text_extractor_config']['test_result']['#title'] = $this->t('Test extractor %plugin', array('%plugin' => $this->getExtractionPluginInformations()['labels'][$extractor_plugin_id]));
      $form['text_extractor_config']['test_result']['#open'] = TRUE;

      $storage = $form_state->getStorage();
      if (empty($storage)) {
        // Put the initial thing into the storage
        $storage = array(
          'extracted_test_text' => $this->t("Extraction doesn't seem to work"),
        );
        $form_state->setStorage($storage);
      }
      $form['text_extractor_config']['test_result']['test_file_path_result']['#markup'] = $storage['extracted_test_text'];
    }
  }

  /**
   * Ajax callback.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   */
  public static function buildAjaxTextExtractorConfigForm(array $form, FormStateInterface $form_state) {
    //We just need to return the relevant part of the form here.
    return $form['text_extractor_config'];
  }

}
