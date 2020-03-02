<?php

namespace Drupal\custom_events\EventSubscriber;

use Drupal\custom_events\Event\UserLoginEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class UserLoginSubscriber.
 * 
 * @package Drupal\custom_events\EventSubscriber
 */
class UserLoginSubscriber implements EventSubscriberInterface {

  /**
   * Database connection.
   * 
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Date formatter.
   * 
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      // Static class constant => method on this class.
      UserLoginEvent::EVENT_NAME => 'onUserLogin',
    ];
  }

  /**
   * React to the user login event dispatched.
   * 
   * @param \Drupal\custom_events\Event\UserLoginEvent $event
   *  Dat event obect yo.
   */
  public function onUserLogin(UserLoginEvent $event) {
    $database = \Drupal::database();
    $dateFormatter = \Drupal::service('date.formatter');

    $account_created = $database->select('users_field_data', 'ud')
      ->fields('ud', ['created'])
      ->condition('ud.uid', $event->account->id())
      ->execute()
      ->fetchField();

    // drupal_set_message(t('Welcome <b>%user</b>, your account was created on %created_date.', ['%user' => \Drupal::currentUser()->getUsername(),'%created_date' => $dateFormatter->format($account_created, 'short')]));
    drupal_set_message(t('Welcome <b>%user</b>, your account was created on %created_date.', ['%user' => \Drupal::currentUser()->getUsername(),'%created_date' => date("H:i:s d-m-Y", substr($account_created, 0, 10))]));
  }
}