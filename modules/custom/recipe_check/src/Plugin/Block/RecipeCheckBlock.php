<?php
/**
 * @file
 * contains \Drupal\recipe_check\Plugin\Block\RecipeCheckBlock
 */
namespace Drupal\recipe_check\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides an 'Recipe Check' Block
 * @Block(
 *  id = "recipe_check_block",
 *  admin_label = @Translation("Recipe Check Block"),
 * )
 */
class RecipeCheckBlock extends BlockBase {
  /**
   * (@inheritdoc)
   */
  public function build() {
    return \Drupal::formBuilder()->getForm('Drupal\recipe_check\Form\RecipeCheckForm');
  }
  
  /**
   * (@inheritdoc)
   */
  public function blockAccess(AccountInterface $account) {
    /** @var \Drupal\node\Entity\Node $node */
  $node = \Drupal::routeMatch()->getParameter('node');
  $nid = $node->nid->value;
  /** @var \Drupal\recipe_check\EnablerService $enabler */
  $enabler = \Drupal::service('recipe_check.enabler');
  if(is_numeric($nid)) {
    if ($enabler->isEnabled($node)) {
      return AccessResult::allowedIfHasPermission($account, 'view recipe_check');
    }
  }
  return AccessResult::forbidden();
  }
}