<?php

/**
 * @file
 * Contains \Drupal\recipe_check\Form\RecipeCheckSettingsForm
 */
namespace Drupal\recipe_check\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Definesa form to configure Recipe Check module settings
 */
class RecipeCheckSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'recipe_check_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'recipe_check.settings'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $request = NULL) {
    $types = node_type_get_names();
    $config = $this->config('recipe_check.settings');
    $form['recipe_check_types'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('The content types to enable Recipe Check button for'),
      '#default_value' => $config->get('allowed_types'),
      '#options' => $types,
    );
    $form['array_filter'] = array('#type' => 'value', '#value' => TRUE);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $allowed_types = array_filter($form_state->getValue('recipe_check_types'));
    sort($allowed_types);
    $this->config('recipe_check.settings')
      ->set('allowed_types', $allowed_types)
      ->save();
    parent::submitForm($form, $form_state);
  }
}