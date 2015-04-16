<?php

namespace Drupal\search_api_attachments\Plugin\SearchApiAttachmentsTextExtractor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api_attachments\TextExtractorPluginBase;

/**
 * @SearchApiAttachmentsTextExtractor(
 *   id = "tika_extractor",
 *   label = @Translation("Tika Extractor"),
 *   description = @Translation("Adds Tika extractor support."),
 * )
 */
class TikaExtractor extends TextExtractorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function extract($file) {
    return 'tika tika tika';
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['tika_path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Path to Tika .jar file'),
      '#description' => $this->t('Enter the full path to tika executable jar file'),
      '#default_value' => $this->configuration['tika_path'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (isset($values['text_extractor_config']['tika_path']) && $values['text_extractor_config']['tika_path'] != 'toto') {
      $form_state->setError($form['text_extractor_config']['tika_path'], $this->t('it should be toto'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['tika_path'] = $form_state->getValue(array('text_extractor_config', 'tika_path'));
    parent::submitConfigurationForm($form, $form_state);
  }

}
