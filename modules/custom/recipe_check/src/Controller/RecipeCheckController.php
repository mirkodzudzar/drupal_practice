<?php

/**
 * @file
 * Contains \Drupal\recipe_check\Controller\RecipeCheckController
 */
namespace Drupal\recipe_check\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Component\Render\FormattableMarkup; 
use Drupal\image\Entity\ImageStyle;

/**
 * Controller for list of all recepies that has been made by some user
 */
class RecipeCheckController extends ControllerBase {
  
  /**
   * Gets all recipes that has been made by logged in user
   * 
   * @return array
   */
  public function load() {
    $uid = \Drupal::currentUser()->id();
    $select = Database::getConnection()->select('recipe_check', 'r');
    // Join the users table, so we can get username of Recipe Check user.
    $select->join('users_field_data', 'u', 'r.uid = u.uid');
    // Join the node table, so we can get the recipe name.
    $select->join('node_field_data', 'n', 'r.nid = n.nid');
    $select->addField('n', 'title');
    // $select->addField('u', 'name', 'username');
    $select->addField('r', 'created');
    $select->addField('r', 'nid');
    // $select->addField('r', 'mail');
    $select->condition('r.uid', $uid);
    $select->orderBy('r.created', 'DESC');
    $entries = $select->execute()->fetchAll(\PDO::FETCH_ASSOC);

    // ->sort('r.created', 'DESC')

    for($i = 0; $i < count($entries); $i++) {
      /** @var \Drupal\Core\Datetime\DateFormatterInterface $formatter */
      $date_formatter = \Drupal::service('date.formatter');

      $entries[$i]['created'] = $date_formatter->formatDiff($entries[$i]['created'], [
        'granularity' => 1,
        'return_as_object' => TRUE,
      ]);
      $entries[$i]['created'] .= ' ago';
      // $entries[$i]['created'] = date("H:i:s d-m-Y", substr($entries[$i]['created'], 0, 10));

      // Get a node storage object.
      $node_storage = \Drupal::entityManager()->getStorage('node');
  
      // Load a single node.
      $node = $node_storage->load($entries[$i]['nid']);
  
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
    // $content['message'] = array(
    //   '#markup' => $this->t('Below is a list of all recipes that you made so far.'),
    // );
    $headers = array(
      t('Recipe'),
      t('I made it'),
    );
    $rows = array();
    foreach ($entries = $this->load() as $entry) {
      // Sanitize each entry
      // $rows[] = array_map('\Drupal\Component\Utility\SafeMarkup::checkPlain', $entry);
      $rows[] = array(
        'data1' => new FormattableMarkup(
          '<a href=":link_url">@name</a><br><a href=":link_url"><img src=":image_url"></a>', 
          [
            ':link_url' => $entry['link_url'] = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $entry['nid']), 
            '@name' => $entry['title'],
            ':image_url' => $entry['image_url']
          ]
        ),
        $entry['created'],
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