<?php

namespace Drupal\search_api_attachments;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;

interface TextExtractorPluginInterface extends PluginFormInterface, ConfigurablePluginInterface {

  public function extract($method, $file);

}
