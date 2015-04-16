<?php

namespace Drupal\search_api_attachments;

use Drupal;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;

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
  public function extract($file) {
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
    $extractor_plugin_id = $form_state->getValue('extraction_method');
    $config = Drupal::configFactory()
        ->getEditable('search_api_attachments.admin_config');
    $config->set($extractor_plugin_id . '_configuration', $this->configuration);
    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return array();
  }

  /**
   * Helper method to get the real path from an uri.
   *
   * @param $uri
   *   The URI of the file, e.g. public://directory/file.jpg.
   * @return mixed
   *   The real path to the file if it is a local file. An URL otherwise.
   */
  public function getRealpath($uri) {
    $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');
    $wrapper = $stream_wrapper_manager->getViaUri($uri);
    $scheme = file_uri_scheme($uri);
    $local_wrappers = $stream_wrapper_manager->getWrappers(StreamWrapperInterface::LOCAL);
    if (in_array($scheme, array_keys($local_wrappers))) {
      return $wrapper->realpath();
    }
    else {
      return $wrapper->getExternalUrl();
    }
  }
}
