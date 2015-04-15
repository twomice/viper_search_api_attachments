<?php

namespace Drupal\search_api_attachments;

use Drupal;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;

abstract class TextExtractorPluginBase extends PluginBase implements TextExtractorPluginInterface {

  function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configuration = $configuration;
  }

  public static function create(array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  public function extract($method, $file) {
    $filename = $file;
    $mode = 'r';
    return fopen($filename, $mode);
  }

  public function getConfiguration() {
    return $this->configuration;
  }

  public function setConfiguration(array $configuration) {
    $this->configuration += $configuration;
  }

  public function defaultConfiguration() {

    return array();
  }

  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $config = Drupal::configFactory()
        ->getEditable('search_api_attachments.admin_config');
    $config->set('text_extractor_config', $this->configuration);
    $config->save();
  }

  public function calculateDependencies() {
    return array();
  }

}
