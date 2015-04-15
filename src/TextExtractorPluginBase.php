<?php

namespace Drupal\search_api_attachments;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Base class for plugins able to extract file content.
 *
 * @ingroup plugin_api
 */
abstract class TextExtractorPluginBase extends PluginBase implements TextExtractorPluginInterface {

  /**
   * {@inheritdoc}
   */
  function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function extract($method, $file) {
    $filename = $file;
    $mode = 'r';
    return fopen($filename, $mode);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration += $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()
        ->getEditable('search_api_attachments.admin_config');
    $config->set('text_extractor_config', $this->configuration);
    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return array();
  }

}
