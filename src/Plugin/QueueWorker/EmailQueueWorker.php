<?php

namespace Drupal\simple_batch\Plugin\QueueWorker;

use Drupal\Core\Annotation\QueueWorker;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\user\Entity\User;

/**
 * Process a queue.
 *
 * @QueueWorker(
 *   id = "my_custom_email",
 *   title = @Translation("Custom email queue"),
 *   cron = {"time" = 45}
 * )
 */
class EmailQueueWorker extends QueueWorkerBase {

  /**
   * @inheritDoc
   */
  public function processItem($data) {
    $mailManager = \Drupal::service('plugin.manager.mail');
    $params = $data;
    $admin_users_id = \Drupal::service('entity_type.manager')
      ->getStorage('user')
      ->getQuery()
      ->condition('status', 1)
      ->condition('roles', 'administrator')
      ->execute();
    foreach ($admin_users_id as $uid) {
      $user = User::load($uid);
      $email = $user->getEmail();
      $mailManager->mail('simple_batch', 'my_custom_email', $email, 'en', $params , $send = TRUE);
    }
  }

}
