<?php

namespace Drupal\search_api_attachments\Plugin\search_api\processor;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\field\Entity\FieldConfig;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;

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
        $field->addValue('test test');
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
