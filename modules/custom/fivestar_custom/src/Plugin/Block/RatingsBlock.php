<?php
/**
 * @file
 * contains \Drupal\fivestar_custom\Plugin\Block\RatingsBlock
 */
namespace Drupal\fivestar_custom\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Database;
use Drupal\Component\Render\FormattableMarkup;  
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Url;
// use Drupal\Core\Session\AccountInterface;
// use Drupal\Core\Access\AccessResult;

/**
 * Provides an 'Ratings block' Block
 * @Block(
 *  id = "ratings_block",
 *  admin_label = @Translation("Ratings block"),
 * )
 */
class RatingsBlock extends BlockBase {

  /**
   * (@inheritdoc)
   */
  public function build() {
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
    $select->range(0, 10);
    $entries = $select->execute()->fetchAll(\PDO::FETCH_ASSOC);
    // Change every rate to be shown as single digit number
    for($i = 0; $i < count($entries); $i++) {
      $entries[$i]['value'] = round($entries[$i]['value'] / 10, 0);
    }
    
    $content = array();
    // $headers = array(
    //   t('Recipe'),
    //   t('Name'),
    //   t('You made it at'),
    //   t('Email'),
    // );
    $rows = array();

    foreach ($entries as $entry) {
      // Sanitize each entry
      // $rows[] = array_map('SafeMarkup::checkPlain', $entry);
      $rows[] = array(
        $entry['value'] . ' ★',
        array('data' => new FormattableMarkup('<a href=":link">@name</a>', 
          [':link' => $entry['link_url'] = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $entry['entity_id']), 
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