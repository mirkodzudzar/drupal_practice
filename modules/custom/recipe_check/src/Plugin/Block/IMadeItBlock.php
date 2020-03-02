<?php
/**
 * @file
 * contains \Drupal\recipe_check\Plugin\Block\IMadeItBlock
 */
namespace Drupal\recipe_check\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Database\Database;
use Drupal\Component\Render\FormattableMarkup;  
use Drupal\Component\Utility\SafeMarkup;

/**
 * Provides an 'I Made It' Block
 * @Block(
 *  id = "i_made_it_block",
 *  admin_label = @Translation("I Made It Block"),
 * )
 */
class IMadeItBlock extends BlockBase {
  /**
   * (@inheritdoc)
   */
  public function build() {
    // return \Drupal::formBuilder()->getForm('Drupal\recipe_check\Form\RecipeCheckForm');
    $uid = \Drupal::currentUser()->id();
    $select = Database::getConnection()->select('recipe_check', 'r');
    // Join the node table, so we can get the recipe name.
    $select->join('node_field_data', 'n', 'r.nid = n.nid');
    $select->addField('n', 'title');
    $select->addField('r', 'nid');
    $select->condition('r.uid', $uid);
    $select->orderBy('r.created', 'DESC');
    $select->range(0, 10);
    $entries = $select->execute()->fetchAll(\PDO::FETCH_ASSOC);
    
    $content = array();
    $rows = array();

    foreach ($entries as $entry) {
      // Sanitize each entry
      // $rows[] = array_map('SafeMarkup::checkPlain', $entry);
      $rows[] = array(
        array('data' => new FormattableMarkup('<a href=":link">@name</a>', 
          [':link' => $entry['link_url'] = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $entry['nid']), 
          '@name' => $entry['title']])
        ),
      );
    }
    $content['table'] = array(
      '#type' => 'table',
      // '#header' => $headers,
      '#rows' => $rows,
      '#empty' => t('No entries available.')
    );

    //Don't cache this page.
    $content['#cache']['max-age'] = 0;

    return $content;
  }
}