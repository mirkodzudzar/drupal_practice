<?php

namespace Drupal\fivestar_custom\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Our fivestar form class. This works on single nodes only, but not on pages where there are more nodes (recipes).
 */
class FivestarForm extends FormBase {
  
  function __construct() {
    $this->connection = Database::getConnection();
    $this->user = \Drupal::currentUser();
    $this->uid = \Drupal::currentUser()->id();
    $this->time = \Drupal::time()->getCurrentTime();
    $this->response = new AjaxResponse();
  }

  /**
   * Form counter.
   *
   * @var int
   */
  private static $form_counter = 0;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    self::$form_counter += 1;

    // For correct submit work set unique name for every form in page.
    return 'fivestar_form_' . self::$form_counter;
  }

  /**
   * (@inheritdoc)
   */
  public function buildForm(array $form, FormStateInterface $form_state, $nid = NULL) {
    $result_def = $this->vote_select($nid);
    $result_avg = $this->result_select($nid, 'vote_average') / 10;
    $result_avg = round($result_avg, 1);
    $result_count = $this->result_select($nid, 'vote_count');

    if($result_count == 1) {
      $vote_string = 'vote';
    } else {
      $vote_string = 'votes';
    }

    if ($result_count == null) {
      $message = "Average: $result_avg";
    } else {
      $message = "Average: $result_avg ($result_count $vote_string)";
    }

    if($result_def == 0) {
      $result_def = "-";
    }

    $ratings = [
      '10' => 1,
      '20' => 2,
      '30' => 3,
      '40' => 4,
      '50' => 5,
      '-' => 'Select rating',
      '0' => 'Cancel rating',
    ];
    
    $logged_id = $this->user->isAuthenticated();
    if ($logged_id === true) {
      $callback = '::setMessage';
    } else {
      $callback = "::setMessageAnonymus";
    }
    // Click on this element triggered from JS side.
    $form['select_rate'] = [
      '#type' => 'radios',
      '#title' => $this->t('Do you like it?'),
      '#options' => $ratings,
      '#default_value' => $result_def,
      '#ajax' => [
        'callback' => $callback,
        'progress' => array(
          'message' => '',
        ), 
      ],
      '#suffix' => "<div class='average_message_$nid'><p>$message</p></div>",
    ];
    
    $form['nid'] = [
      '#type' => 'hidden',
      '#value' => $nid,
    ];
    
    $form['url'] = array(
      '#type' => 'textfield',
      '#placeholder' => $this->t('Url'),
      '#default_value' => '',
      '#size' => 30,
      '#maxlength' => 128,
    );

    $form['#attributes']['class'][] = 'fivestar_url';
    
    return $form;
  }
  // Inserting data in 'fivestar_custom_vote' table
  protected function vote_insert($rate, $nid) {
    $insert = $this->connection->insert('fivestar_custom_vote');
    $insert->fields([
      'type' => 'vote',
      'entity_type' => 'node',
      'entity_id' => $nid,
      'value' => $rate,
      'value_type' => 'percent',
      'user_id' => $this->uid,
      'timestamp' => $this->time,
      'field_name' => NULL,
    ]);
    return $insert->execute();
  }
  // Inserting data in 'fivestar_custom_result' table
  protected function result_insert($rate, $nid, $function) {
    $insert = $this->connection->insert('fivestar_custom_result');
    $insert->fields([
      'type' => 'vote',
      'entity_type' => 'node',
      'entity_id' => $nid,
      'value' => $rate/1,
      'function' => $function,
      'timestamp' => $this->time,
    ]);
    return $insert->execute();
  }
  // Selecting data from 'fivestar_custom_vote' table
  protected function vote_select($nid) {
    $select = $this->connection->select('fivestar_custom_vote', 't');
    $select->addField('t', 'value');
    $select->condition('entity_id', $nid);
    $select->condition('user_id', $this->uid);
    $select = $select->execute()->fetch();
    if(isset($select->value)) { 
      return (float) $select->value;
    }
  }
  // Selecting data from 'fivestar_custom_result' table
  protected function result_select($nid, $function) {
    $select = $this->connection->select('fivestar_custom_result', 't');
    $select->addField('t', 'value');
    $select->condition('entity_id', $nid);
    $select->condition('function', $function);
    $select = $select->execute()->fetch();
    if(isset($select->value)) { 
      return (float) $select->value;
    }
  }
  // Updating data in 'fivestar_custom_vote' table
  protected function vote_update($value, $nid) {
    $update = $this->connection->update('fivestar_custom_vote');
    $update->fields(['value' => $value, 'timestamp' => $this->time]);
    $update->condition('entity_id', $nid);
    $update->condition('user_id', $this->uid);
    return $update->execute();
  }
  // Updating data in 'fivestar_custom_result' table
  protected function result_update($value, $nid, $function) {
    $update = $this->connection->update('fivestar_custom_result');
    $update->fields(['value' => $value, 'timestamp' => $this->time]);
    $update->condition('entity_id', $nid);
    $update->condition('function', $function);
    return $update->execute();
  }
  // Deleting data from 'fivestar_custom_vote' table
  protected function vote_delete($nid) {
    $delete = $this->connection->delete('fivestar_custom_vote');
    $delete->condition('entity_id', $nid);
    $delete->condition('user_id', $this->uid);
    return $delete->execute();
  }
  // Deleting data from 'fivestar_custom_result' table
  protected function result_delete($nid) {
    $delete = $this->connection->delete('fivestar_custom_result');
    $delete->condition('entity_id', $nid);
    return $delete->execute();
  }
  // Message for users to show them which action they made
  protected function fivestar_message($fivestar_message) {
    return $this->response->addCommand(
      new HtmlCommand(
        '.custom_message',
        "<div class='custom_message_screen'><span>" . $this->t($fivestar_message) . "</span></div>",
      ),
    );
  }
  // This is message/text beyond the rate form to show an average results of voting for recipe
  protected function average_message($nid) {
    $result_avg = $this->result_select($nid, 'vote_average') / 10;
    $result_avg = round($result_avg, 1);
    $result_count = $this->result_select($nid, 'vote_count');
    if($result_count == 1) {
      $vote_string = 'vote';
    } else {
      $vote_string = 'votes';
    }

    if ($result_count == null) {
      $message = "Average: $result_avg";
    } else {
      $message = "Average: $result_avg ($result_count $vote_string)";
    }

    return $this->response->addCommand(
      new HtmlCommand(
        ".average_message_$nid",
        "<p class='average_message'>$message</p>",
      ),
    );
  }  

  /**
   * Ajax callback for anonymous users.
   * This will be called when we click on some star or cancel button.
   */
  public function setMessageAnonymus(array &$form, FormStateInterface $form_state, $form_id) {
    // $nid = $form['select_rate']['#id'];
    // $nid = $form['nid']['#value'];
    $nid = $form_state->getValue('nid');
    if ($form_state->getValue('url') != null) {
      $response = $this->fivestar_message('Try to fill out the form again.');
    }
    else {
      $url = Url::fromRoute('user.login');
      // $response = $this->fivestar_message(Link::fromTextAndUrl($this->t('Log in '), $url)->toString() . " first to rate this recipe.", $nid);
      $response = $this->fivestar_message('Log in first to rate this recipe.');
    }

    return $response;
  }
  
  /**
   * Ajax callback: update fivestar form after voting.
   * This will be called when we click on some star or cancel button.
   */
  public function setMessage(array &$form, FormStateInterface $form_state, $form_id) {
    // $nid = $form['select_rate']['#id'];
    // $nid = $form['nid']['#value'];
    // $nid = $form_state->getValues();
    // die('<pre>' . print_r($nid, 1) . '</pre>');
    $nid = $form_state->getValue('nid');
    // Checking if hidden field is filled. This may protect us from spam bots
    if ($form_state->getValue('url') != null) {
      $response = $this->fivestar_message('Try to fill out the form again.');
    }
    else {
      $rate = (float) $form_state->getValue('select_rate');
    
      // Check if user already rated this node(recipe)
      $select = $this->connection->select('fivestar_custom_vote', 'fcv');
      $select->fields('fcv', ['entity_id', 'user_id']);
      $select->condition('entity_id', $nid);
      $select->condition('user_id', $this->uid);
      $select_vote_table = $select->execute();

      // Check if table 'fivestar_custom_result' already contains rates of this node
      $select = $this->connection->select('fivestar_custom_result', 'fcr');
      $select->fields('fcr', ['entity_id']);
      $select->condition('entity_id', $nid);
      $select_result_table = $select->execute();
      
      // Check if logged in user have already voted on this node (recipe)
      if(empty($select_vote_table->fetchCol())) {
        // Check if star is chosed
        if($rate == '10' || $rate == '20' || $rate == '30' || $rate == '40' || $rate == '50') {
          $this->vote_insert($rate, $nid);
          
          // Check if table 'fivestar_custom_result' contains results about current node
          if(empty($select_result_table->fetchCol())) {
            $this->result_insert($rate/1, $nid, 'vote_average');
            $this->result_insert(1, $nid, 'vote_count');
            $this->result_insert($rate, $nid, 'vote_sum');
          } 
          // Table 'fivestar_custom_result' does not contain results about current node
          else {
            $vote_sum = $this->result_select($nid, 'vote_sum') + $rate;
            $vote_count = $this->result_select($nid, 'vote_count') + 1;
            $vote_average = $vote_sum / $vote_count;

            $this->result_update($vote_sum, $nid, 'vote_sum');
            $this->result_update($vote_count, $nid, 'vote_count');
            $this->result_update($vote_average, $nid, 'vote_average');
          }
          $response = $this->fivestar_message('You rated this recipe.');
        }
        // Check if cancel is chosed, nothing will happen because there is no results in table 'fivestar_custom_vote'
        else {
          $response = $this->fivestar_message('There is no retings by you jet.');
        }
      }
      // If there is some result already in table 'fivestar_custom_vote'
      else {
        // Check if we chosed some star for rating
        if($rate == '10' || $rate == '20' || $rate == '30' || $rate == '40' || $rate == '50') {
          $result = $this->vote_select($nid);

          $vote_sum = $this->result_select($nid, 'vote_sum') - $result;
          $vote_count = $this->result_select($nid, 'vote_count') - 1;

          $vote_sum = $vote_sum + $rate;
          $vote_count = $vote_count + 1;
          $vote_average = $vote_sum / $vote_count;

          $this->result_update($vote_sum, $nid, 'vote_sum');
          $this->result_update($vote_count, $nid, 'vote_count');
          $this->result_update($vote_average, $nid, 'vote_average');

          $this->vote_update($rate, $nid);
          $response = $this->fivestar_message('Your vote has been changed.');
        }
        // If we chose cancel button, results from table 'fivestar_custom_vote' disappears and table 'fivestar_custom_result' will be changed
        elseif($rate == '0') {
          $result = $this->vote_select($nid);

          $this->vote_delete($nid);

          $vote_sum = $this->result_select($nid, 'vote_sum') - $result;
          $vote_count = $this->result_select($nid, 'vote_count') - 1;
          $vote_average = $vote_sum / $vote_count;
          // Delete data of some recipe from table 'fivestar_custom_result' if there is no records anymore
          if($vote_count == 0) {
            $this->result_delete($nid);
          }
          // Update data in table 'fivestar_custom_result'
          else {
            $this->result_update($vote_sum, $nid, 'vote_sum');
            $this->result_update($vote_count, $nid, 'vote_count');
            $this->result_update($vote_average, $nid, 'vote_average');
          }
          $response = $this->fivestar_message('Your vote has been deleted.');
        }
        else {
          $response = $this->fivestar_message('There is no retings by you jet.');
        }
      }
    }

    $response = $this->average_message($nid);
    return $response;
  }

  /**
   * (@inheritdoc)
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}