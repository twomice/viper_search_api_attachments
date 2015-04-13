<?php

namespace Drupal\search_api_attachments\Plugin\search_api\processor;

use Drupal\Core\TypedData\DataDefinition;
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

    $definition = array(
      'label' => $this->t('Search api attachments'),
      'description' => $this->t('todo'),
      'type' => 'string',
    );
    $properties['search_api_attachments'] = new DataDefinition($definition);
  }
  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array &$items) {

    foreach ($items as $item) {
      if (!($field = $item->getField('search_api_attachments'))) {
        continue;
      }
      $field->addValue('test test');
      

    }
  }
  
    protected function getFileFields() {
    $file_fields = array();
    $field_entities = entity_load_multiple('field_config');
    foreach ($field_entities as $field_entity) {
      // Restrict to file fields.
      if ($field_entity->get('field_type') == 'file') {
        $field_name = $field_entity->get('field_name');
        $file_fields[$field_name] = $field_name;
      }
    }
    return $file_fields;

//      
//    $fields = array();
//    foreach (field_info_fields() as $name => $field) {
//      if ($field['type'] == 'file' && array_key_exists($this->index->getEntityType(), $field['bundles'])) {
//        $ret[$name] = $field;
//      }
//    }
//   
  }
}