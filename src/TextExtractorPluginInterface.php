<?php

namespace Drupal\search_api_attachments;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;

/**
 * Provides an interface for a plugin that extracts files content.
 *
 * @ingroup plugin_api
 */
interface TextExtractorPluginInterface extends PluginFormInterface, ConfigurablePluginInterface {

  /**
   * Extract method.
   *
   * @param type $file
   *   The file object.
   *
   * @return string
   *   Teh file extracted content.
   */
  public function extract($file);

}
