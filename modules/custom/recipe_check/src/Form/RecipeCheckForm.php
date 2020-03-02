<?php

/**
 * @file
 * Contains \Drupal\recipe_check\Form\RecipeCheckForm
 */
namespace Drupal\recipe_check\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\Formbase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Url;
use Drupal\Core\Link;
// use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides an Recipe Check button form (only button appears)
 */
class RecipeCheckForm extends FormBase {
  
  function __construct() {
    $entity = \Drupal::routeMatch()->getParameter('node');
    if ($entity instanceof \Drupal\node\NodeInterface) {
      $this->nid = $entity->id();
    }
    $this->connection = Database::getConnection();
    $this->user = \Drupal::currentUser();
    $this->uid = \Drupal::currentUser()->id();
    $this->time = \Drupal::time()->getCurrentTime();
    $this->response = new AjaxResponse();
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'recipe_check_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $logged_id = $this->user->isAuthenticated();
    if ($logged_id === true) {
      $callback = '::setMessage';
    } else {
      $callback = '::setMessageAnonymus';
    }
    
    if(empty($this->select_recipe())) {
      $check_mark = '';
    } else {
      $check_mark = '✔';
    }

    $form['actions'] = [
      '#type' => 'button',
      '#value' => t('I made it!'),
      '#id' => 'i_made_it',
      '#ajax' => [
        'callback' => $callback,
        'event' => 'click',
        'progress' => array(
          'message' => '',
        ),
      ],
      '#suffix' => "<div class='check_mark'><p>$check_mark</p></div>",
    ];    

    // $form['message'] = [
    //   '#type' => 'markup',
    //   '#markup' => '<div class="result_message"></div>',
    // ];

    $form['text'] = [
      '#type' => 'markup',
      '#markup' => "<div class='recipe_check_counter_text'><p>" . $this->t('%counter people have already made this recipe.', array('%counter' => $this->counter())) . "</p></div>",
    ];

    $form['url'] = array(
      '#type' => 'textfield',
      '#placeholder' => $this
        ->t('Url'),
      '#default_value' => '',
      '#size' => 30,
      '#maxlength' => 128,
      '#id' => 'i_made_it_url',
    );
    
    return $form;
  }

  protected function select_recipe() {
    $select = $this->connection->select('recipe_check', 'r');
    $select->fields('r', array('nid'));
    $select->condition('nid', $this->nid);
    $select->condition('uid', $this->uid);
    return $select->execute()->fetchCol();
  }

  protected function counter() {
    $select = $this->connection->select('recipe_check', 'r');
    $select->fields('r', array('nid'));
    $select->condition('nid', $this->nid);
    return $select->countQuery()->execute()->fetchField();
  }

  protected function result_message($result_message) {
    return $this->response->addCommand(
      new HtmlCommand(
        '.custom_message',
        "<div class='custom_message_screen'><span>" . $this->t($result_message) . "</span></div>",
      ),
    );
  }

  protected function counter_message($counter) {
    return $this->response->addCommand(
      new HtmlCommand(
        '.recipe_check_counter_text',
        '<p>' . $this->t('%counter people have already made this recipe.', array('%counter' => $counter)) . '</p>',
      ),
    );
  }

  protected function check_mark() {
    if(empty($this->select_recipe())) {
      $check_mark = '';
    } else {
      $check_mark = '✔';
    }

    return $this->response->addCommand(
      new HtmlCommand(
        '.check_mark',
        "<p>$check_mark</p>",
      ),
    );
  }


  public function setMessageAnonymus() {
    if ($form_state->getValue('url') != null) {
      $response = $this->result_message("Try to fill out the form again.");
    }
    else {
      $url = Url::fromRoute('user.login');
      // $response = $this->result_message(Link::fromTextAndUrl($this->t('Log in '), $url)->toString() . " first to check this recipe");
      $response = $this->result_message('Log in first to rate this recipe.');
    }

    return $response;
  }

  public function setMessage(array &$form, FormStateInterface $form_state) {
    // Checking if hidden field is filled. This may protect us from spam bots
    if ($form_state->getValue('url') != null) {
      $response = $this->result_message("Try to fill out the form again.");
    }
    else {
      // Check if email already is set for this node(recipe)
      if(empty($this->select_recipe())) {
        $insert = $this->connection->insert('recipe_check');
        $insert->fields([
          'mail' => $this->user->getEmail(),
          'nid' => $this->nid,
          'uid' => $this->uid,
          'created' => $this->time,
        ]);
        $insert->execute();
        $response = $this->result_message("Thanks for your effort. We hope you recommend this recipe to others.");
        $response = $this->counter_message($this->counter());
        $response = $this->check_mark();
      }
      else {
        // We found a row with this nid and email
        $delete = $this->connection->delete('recipe_check');
        $delete->condition('nid', $this->nid);
        $delete->condition('uid', $this->uid);
        $delete->execute();
        $response = $this->result_message("You have unchecked the recipe");
        $response = $this->counter_message($this->counter());
        $response = $this->check_mark();
      }
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    //
  }
}