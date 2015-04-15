<?php

namespace Drupal\search_api_attachments\Plugin\SearchApiAttachmentsTextExtractor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api_attachments\TextExtractorPluginBase;

/**
 * @SearchApiAttachmentsTextExtractor(
 *   id = "solr_extractor",
 *   label = @Translation("Solr Extractor"),
 *   description = @Translation("Adds Solr extractor support."),
 * )
 */
class SolrExtractor extends TextExtractorPluginBase {

  public function extract($method, $file) {
    return 'solr solr solr';
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['solr_path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Solr apath'),
      '#description' => $this->t('solr'),
      '#default_value' => $this->configuration['solr_path'],
    );
    return $form;
  }

  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (isset($values['text_extractor_config']['solr_path']) && $values['text_extractor_config']['solr_path'] != 'titi') {
      $form_state->setError($form['text_extractor_config']['solr_path'], $this->t('it should be titi'));
    }
  }

  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['solr_path'] = $form_state->getValue(array('text_extractor_config', 'solr_path'));
    parent::submitConfigurationForm($form, $form_state);
  }

}
