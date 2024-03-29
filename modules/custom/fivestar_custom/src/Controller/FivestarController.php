<?php

/**
 * @file
 * Contains \Drupal\recipe_check\Controller\RecipeCheckController
 */
namespace Drupal\fivestar_custom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Component\Render\FormattableMarkup; 
use Drupal\Component\Utility\SafeMarkup;
use Drupal\image\Entity\ImageStyle;

/**
 * Controller for list of all recepies that has been made by some user
 */
class FivestarController extends ControllerBase {
  
  /**
   * Gets all recipes that has been made by logged in user
   * 
   * @return array
   */
  public function load() {
    $uid = \Drupal::currentUser()->id();
    $select = Database::getConnection()->select('fivestar_custom_vote', 'fcv');
    // Join the node table, so we can get the recipe name.
    $select->join('node_field_data', 'n', 'fcv.entity_id = n.nid');
    $select->addField('fcv', 'entity_id');
    $select->addField('fcv', 'value');
    $select->addField('n', 'title');
    $select->addField('fcv', 'timestamp');
    $select->condition('fcv.user_id', $uid);
    $select->orderBy('fcv.value', 'DESC');
    $select->orderBy('fcv.timestamp', 'DESC');
    $entries = $select->execute()->fetchAll(\PDO::FETCH_ASSOC);
    // Change every date to be more human friendly
    // Change every rate to be shown as single digit number
    for($i = 0; $i < count($entries); $i++) {
      /** @var \Drupal\Core\Datetime\DateFormatterInterface $formatter */
      $date_formatter = \Drupal::service('date.formatter');

      $entries[$i]['timestamp'] = $date_formatter->formatDiff($entries[$i]['timestamp'], [
        'granularity' => 1,
        'return_as_object' => TRUE,
      ]);
      $entries[$i]['timestamp'] .= ' ago';
      // $entries[$i]['timestamp'] = date("H:i:s d-m-Y", substr($entries[$i]['timestamp'], 0, 10));
      $entries[$i]['value'] = round($entries[$i]['value'] / 10, 0);

      // Get a node storage object.
      $node_storage = \Drupal::entityManager()->getStorage('node');
  
      // Load a single node.
      $node = $node_storage->load($entries[$i]['entity_id']);
  
      if ($node->field_image->entity != NULL) {
        $style = ImageStyle::load('term');
        $entries[$i]['image_url'] = file_create_url($style->buildUrl($node->field_image->entity->getFileUri()));
      }
    }

    return $entries;
  }

  /**
   * Create the 'I made it' page
   * 
   * @return array
   * Render array for list output
   */
  public function list() {
    $content = array();
    
    $headers = array(
      'Rating' => t('Rating'),
      'Recipe' => t('Recipe'),
      'Rated' => t('Rated'),
    );

    $rows = array();

    foreach ($entries = $this->load() as $entry) {
      // Sanitize each entry
      // $rows[] = array_map('SafeMarkup::checkPlain', $entry);
      $rows[] = array(
        $entry['value'] . ' ★',
        'data1' => new FormattableMarkup(
          '<a href=":link_url">@name</a><br><a href=":link_url"><img src=":image_url"></a>', 
          [
            ':link_url' => $entry['link_url'] = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $entry['entity_id']), 
            '@name' => $entry['title'],
            ':image_url' => $entry['image_url']
          ]
        ),
        $entry['timestamp'],
      );
    }

    $content['table'] = array(
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => t('No entries available.')
    );

    //Don't cache this page.
    $content['#cache']['max-age'] = 0;

    return $content;
  }
}