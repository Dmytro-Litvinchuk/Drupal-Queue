<?php

namespace Drupal\simple_batch\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Process a queue.
 *
 * @QueueWorker(
 *   id = "my_custom_email",
 *   title = @Translation("Custom email queue"),
 *   cron = {"time" = 45}
 * )
 */
class EmailQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * EmailQueueWorker constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Mail\MailManagerInterface $mailManager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityManager
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              MailManagerInterface $mailManager,
                              EntityTypeManagerInterface $entityManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mailManager = $mailManager;
    $this->entityManager = $entityManager;
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.mail'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * @inheritDoc
   */
  public function processItem($data) {
    $mail = $this->mailManager;
    $params = $data;
    $adminUsersId = $this->entityManager
      ->getStorage('user')
      ->getQuery()
      ->condition('status', 1)
      ->condition('roles', 'administrator')
      ->execute();
    // Get emails for all users with admin role.
    foreach ($adminUsersId as $uid) {
      $user = User::load($uid);
      // Check exist.
      if (!isset($to)) {
        $to = $user->getEmail();
      }
      else {
        // Return string value (example@gmail, some@mail.com)
        $to .= ', ' . $user->getEmail();
      }
    }
    $mail->mail('simple_batch', 'my_custom_email', $to, 'en', $params, TRUE);
  }

}
