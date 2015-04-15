<?php

namespace Drupal\search_api_attachments;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

class TextExtractorPluginManager extends DefaultPluginManager {

  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/TextExtractor', $namespaces, $module_handler, 'Drupal\search_api_attachments\TextExtractorPluginInterface', 'Drupal\search_api_attachments\Annotation\TextExtractor');
    $this->alterInfo('text_extractor_info');
    $this->setCacheBackend($cache_backend, 'text_extractor_plugins');
  }

}
