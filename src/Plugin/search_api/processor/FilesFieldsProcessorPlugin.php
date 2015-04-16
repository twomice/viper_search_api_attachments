<?php

namespace Drupal\search_api_attachments\Plugin\search_api\processor;

use Drupal;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\field\Entity\FieldConfig;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api_attachments\TextExtractorPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @SearchApiProcessor(
 *   id = "file_attachments",
 *   label = @Translation("File attachments"),
 *   description = @Translation("Adds the file attachments content to the indexed data."),
 *   stages = {
 *     "preprocess_index" = 0
 *   }
 * )
 */
class FilesFieldsProcessorPlugin extends ProcessorPluginBase {

  /**
   * Name of the config being edited.
   */
  const CONFIGNAME = 'search_api_attachments.admin_config';

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, TextExtractorPluginManager $textExtractorPluginManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->textExtractorPluginManager = $textExtractorPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = new static($configuration, $plugin_id, $plugin_definition, $container->get('plugin.manager.search_api_attachments.text_extractor'));

    /** @var \Drupal\Core\StringTranslation\TranslationInterface $translation */
    $translation = $container->get('string_translation');
    $plugin->setStringTranslation($translation);

    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function alterPropertyDefinitions(array &$properties, DatasourceInterface $datasource = NULL) {
    if ($datasource) {
      return;
    }
    foreach ($this->getFileFields() as $field_name => $label) {
      $definition = array(
        'label' => $this->t('Search api attachments: !label', array('!label' => $label)),
        'description' => $this->t('Search api attachments: !label', array('!label' => $label)),
        'type' => 'string',
      );
      $properties['search_api_attachments_' . $field_name] = new DataDefinition($definition);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array &$items) {

    foreach ($items as $item) {
      foreach ($this->getFileFields() as $field_name => $label) {
        if (!($field = $item->getField('search_api_attachments_' . $field_name))) {
          continue;
        }
        $config = Drupal::configFactory()
            ->getEditable(static::CONFIGNAME);
        $extractor_plugin_id = $config->get('extraction_method');

        if ($extractor_plugin_id) {
          $file = array();
          $extractor_plugin = $this->textExtractorPluginManager->createInstance($extractor_plugin_id);
          $extraction = $extractor_plugin->extract($file);
          $field->addValue($extraction);
        }
      }
    }
  }

  protected function getFileFields() {
    $file_fields = array();
    // Retrieve file fields of indexed bundles.
    foreach ($this->getIndex()->getDatasources() as $datasource) {
      foreach ($datasource->getPropertyDefinitions() as $property) {
        if ($property instanceof FieldConfig) {
          if ($property->field_type == 'file') {
            $file_fields[$property->field_name] = $property->label;
          }
        }
      }
    }
    return $file_fields;
  }

}
