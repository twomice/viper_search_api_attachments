<?php

namespace Drupal\search_api_attachments\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class ContentManager.
 *
 * @package Drupal\insights_rssfeed\Controller
 */
class Solr_search extends ControllerBase {
    /**
     * this function is used for rssfeed rendering data.
     */

    /**
     * {@inheritdoc}
     */
    public function solrdata() {
      
       

        $settings = \Drupal\Core\Site\Settings::get('scms_solr_config');

        $config = array(
            'adapter' => 'Solarium\Core\Client\Adapter\Guzzle',
            'endpoint' => array(
                'localhost' => $settings
            )
        );
        $client = new \Solarium\Client($config);
        $query = $client->createSelect();
        $returned_fields = array(
            'id',
            'itm_upload',
            'tm_X3b_und_title',
            'tm_X3b_und_field_description',
            'ss_type',
            'its_uid',
            'its_taxonomy_vocabulary_3'
        );

        $query->setFields(array_unique($returned_fields));
        $ss_upload = \Drupal::request()->query->get('ss_upload');
        $conteny_type = \Drupal::entityTypeManager()
                  ->getStorage('node_type')
                  ->loadMultiple();

                  unset($conteny_type['webform']);
                  unset($conteny_type['event']);
                  unset($conteny_type['jmol']);
                  unset($conteny_type['poll']);
                  unset($conteny_type['story']);

                    $options = [];
                    foreach ($conteny_type as $node_type) {
                      $options[] = array(
                                          'id' => $node_type->id(),
                                          'name' => $node_type->label()
                                         );
                    }



                  $vid = 'vocabulary_3';
                    $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
                    foreach ($terms as $term) {
                     $term_data[] = array(
                      'id' => $term->tid,
                      'name' => $term->name
                     );
                    }

        if($ss_upload == null){
            $report_count == 0;
        }else{


        $page = \Drupal::request()->query->get('page'); 
        $type = \Drupal::request()->query->get('type'); 
        $sub = \Drupal::request()->query->get('sub'); 
        $startRows = $page * 10;

        if($ss_upload != null){
        $fq[] = 'tm_X3b_und_saa_upload:'.$ss_upload.'';
        
        }
        if($type != null){
        $fq[] = 'ss_type:'.$type.'';
        
        }
        if($sub != null){
        $fq[] = 'its_taxonomy_vocabulary_3:'.$sub.'';
        
        }

        $query->addParam('fq', $fq); 

        $query->setStart($startRows)->setRows(10);


        $result_set = $client->select($query);
        $resultData = $result_set->getData();
        //dump($resultData);

         $result = $resultData['response']['docs'];
         $report_count = $resultData['response']['numFound'];
         $allPager = $report_count/10;
         foreach($result as $key => $res){
            $explodeArray = explode('/', $res['id']);
            $title = $res['tm_X3b_und_title'][0];
            if($explodeArray[1] != null){
                $explodeNode = explode(':', $explodeArray[1]);
            }
             $stype = $res['ss_type'];
             $nid = $explodeNode[0];
             $node = \Drupal::entityManager()->getStorage('node')->load($nid);
             $termload = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($res['its_taxonomy_vocabulary_3']);
             $user = \Drupal\user\Entity\User::load($res['its_uid']);
             $resultArray[$key]['title'] = $title;
             $resultArray[$key]['desc'] = $res['tm_X3b_und_field_description'][0];
             $resultArray[$key]['user'] = $user->getDisplayName();
             $resultArray[$key]['type'] = $node->type->entity->label();
             $resultArray[$key]['nid'] = $nid;
             $resultArray[$key]['uid'] = $res['its_uid'];
             $resultArray[$key]['disc'] = $termload->label();
         }
     }
         return [
                    '#cache' => array('max-age' => 0),
                    '#theme' => 'searchApi',
                    '#report' => $resultArray,
                    '#query' => $ss_upload,
                    '#rcount' => $report_count,
                    '#allpager' => $allPager,
                    '#page' => $page,
                    '#type' => $type,
                    '#sub' => $sub,
                    '#conteny_type' => $options,
                    '#term' => $term_data


                ];
    }

    }
