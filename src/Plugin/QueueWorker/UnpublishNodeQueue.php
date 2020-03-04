<?php

namespace Drupal\simple_batch\Plugin\QueueWorker;

use Drupal\Core\Annotation\QueueWorker;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class UnpublishNodeQueue
 *
 * @QueueWorker(
 *   id = "unpublish_node_queue",
 *   title = @Translation("Custom unpublish node queue"),
 *   cron = {"time" = 45}
 * )
 */
class UnpublishNodeQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * UnpublishQueueWorker constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityManager
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityTypeManagerInterface $entityManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('entity_type.manager')
    );
  }

  /**
   * @inheritDoc
   */
  public function processItem($data) {
    $node = $this->entityManager
      ->getStorage('node')
      ->load($data);
    if ($node instanceof NodeInterface && $node->isPublished()) {
      $node->setPublished(FALSE);
      $node->save();
    }
  }

}
