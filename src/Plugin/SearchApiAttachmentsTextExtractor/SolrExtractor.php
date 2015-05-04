<?php

namespace Drupal\search_api_attachments\Plugin\SearchApiAttachmentsTextExtractor;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Entity\Server;
use Drupal\search_api_attachments\TextExtractorPluginBase;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

/**
 * @SearchApiAttachmentsTextExtractor(
 *   id = "solr_extractor",
 *   label = @Translation("Solr Extractor"),
 *   description = @Translation("Adds Solr extractor support."),
 * )
 */
class SolrExtractor extends TextExtractorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function extract($file) {
    $filepath = $this->getRealpath($file->getFileUri());
    // Load the chosen Solr server entity.
    $conditions = array(
      'status' => TRUE,
      'id' => $this->configuration['solr_server']
    );
    $server = entity_load_multiple_by_properties('search_api_server', $conditions);
    $server = reset($server);
    // Get the Solr backend.
    $backend = $server->getBackend();
    // Initialise the Client.
    $client = $backend->getSolrConnection();
    // Create the Query.
    $query = $client->createExtract();
    //setExtractOnly is only available in solarium 3.3.0 and up.
    $query->setExtractOnly(TRUE);
    $query->setFile($filepath);
    // Execute the query.
    $result = $client->extract($query);
    $response = $result->getResponse();
    $json_data = $response->getBody();
    $array_data = Json::decode($json_data);
    // $array_data contains json array with two keys : [filename] that contains the
    // extracted text we need and [filename]_metadata that contains some extra
    // metadata.
    $xml_data = $array_data[$filepath];
    // We need to get only what is in body tag.
    $xmlencoder = new XmlEncoder();
    $dom_data = $xmlencoder->decode($xml_data);
    $dom_data = $dom_data['body'];

    $htmlencoder = new XmlEncoder();
    $htmlencoder = $htmlencoder->encode($dom_data, 'xml');

    $body = strip_tags($htmlencoder);
    return $body;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = array();
    $conditions = array(
      'status' => TRUE,
      'backend' => 'search_api_solr',
    );

    $search_api_solr_servers = entity_load_multiple_by_properties('search_api_server', $conditions);
    $options = array();
    foreach ($search_api_solr_servers as $solr_server) {
      $options[$solr_server->id()] = $solr_server->label();
    }

    $form['solr_server'] = array(
      '#type' => 'select',
      '#title' => $this->t('Solr server'),
      '#description' => $this->t('Select the solr server you want to use.'),
      '#empty_value' => '',
      '#options' => $options,
      '#default_value' => $this->configuration['solr_server'],
      '#required' => TRUE
    );

    //@todo test connection live
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (isset($values['text_extractor_config']['solr_server']) && $values['text_extractor_config']['solr_server'] == '') {
      $form_state->setError($form['text_extractor_config']['solr_server'], $this->t('Please choose the solr server to use for extraction.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['solr_server'] = $form_state->getValue(array('text_extractor_config', 'solr_server'));
    parent::submitConfigurationForm($form, $form_state);
  }

}
