<?php

/**
 * Contains \Drupal\recipe_check\Controller\ReportController
 */
namespace Drupal\recipe_check\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;

/**
 * Controller for Recipe Check report
 */
class ReportController extends ControllerBase {

  /**
   * Gets all Recipe Checks for all nodes(recipes).
   * 
   * @return array
   */
  protected function load() {
    $select = Database::getConnection()->select('recipe_check', 'r');
    // Join the users table, so we can get username of Recipe Check user.
    $select->join('users_field_data', 'u', 'r.uid = u.uid');
    // Join the node table, so we can get the recipe name.
    $select->join('node_field_data', 'n', 'r.nid = n.nid');
    $select->addField('u', 'name', 'username');
    $select->addField('n', 'title');
    $select->addField('r', 'mail');
    $select->addField('r', 'created');
    $select->orderBy('r.created', 'DESC');
    $entries = $select->execute()->fetchAll(\PDO::FETCH_ASSOC);

    for($i = 0; $i < count($entries); $i++) {
      $entries[$i]['created'] = date("H:i:s d-m-Y", substr($entries[$i]['created'], 0, 10));
    }
    
    return $entries;
  }

  /**
   * Create the report page
   * 
   * @return array
   *  Render array for report output.
   */
  public function report() {
    $content = array();
    $content['message'] = array(
      '#markup' => $this->t('Below is a list of all recipes that are checked by some user.'),
    );
    $headers = array(
      t('Name'),
      t('Recipe'),
      t('Email'),
      t('Created at'),
      // t('Delete'),
    );
    $rows = array();
    foreach ($entries = $this->load() as $entry) {
      //Sanitize each entry.
      $rows[] = array_map('\Drupal\Component\Utility\SafeMarkup::checkPlain', $entry) ;
    }

    // $content['button'] = [
    //   '#type' => 'submit',
    //   '#value' => 'Delete it',
    // ];

    $content['table'] = array(
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => t('No entries available.'),
    );

    //Don't cache this page.
    $content['#cache']['max-age'] = 0;
    
    return $content;
  }
}