<?php

/**
 * @file
 * Contains \Drupal\recipe_check\EnablerService
 */
namespace Drupal\recipe_check;

use Drupal\Core\Database\Database;
use Drupal\node\Entity\Node;

/**
 * Defines a service for managing Recipe Check enabled for nodes.
 */
class EnablerService {

  /**
   * Constructor.
   */
  public function __construct() {

  }

  /**
   * Sets an individual node to be Recipe Check enabled
   *
   * @param \Drupal\node\Entity\Node $node
   */
  public function setEnabled(Node $node) {
    if (!$this->isEnabled($node)) {
      $insert = Database::getConnection()->insert('recipe_check_enabled');
      $insert->fields(array('nid'), array($node->id()));
      $insert->execute();
    }
  }

  /**
   * Checks if an idvividual node is Recipe Check enabled.
   * 
   * @param \Drupal\node\Entity\Node $node
   * 
   * @return bool
   *  Whether the node is enabled for the Recipe Check functionality.
   */
  public function isEnabled(Node $node) {
    if ($node->isNew()) {
      return FALSE;
    }
    $select = Database::getConnection()->select('recipe_check_enabled', 're');
    $select->fields('re', array('nid'));
    $select->condition('nid', $node->id());
    $results = $select->execute();
    return !empty($results->fetchCol());
  }

  /**
   * Deletes enabled settings for an individual node(recipe).
   * 
   * @param \Drupal\node\Entity\Node $node
   */
  public function delEnabled(Node $node) {
    $delete = Database::getConnection()->delete('recipe_check_enabled');
    $delete->condition('nid', $node->id());
    $delete->execute();
  }
}