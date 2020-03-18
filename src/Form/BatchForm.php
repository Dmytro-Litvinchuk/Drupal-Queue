<?php

namespace Drupal\simple_batch\Form;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\SuspendQueueException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class BatchForm extends FormBase {

  /**
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * @var \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  protected $queueManager;

  protected $batchBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(QueueFactory $queue, QueueWorkerManagerInterface $queue_manager) {
    $this->queueFactory = $queue;
    $this->queueManager = $queue_manager;
    $this->batchBuilder = new BatchBuilder();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('queue'),
      $container->get('plugin.manager.queue_worker')
    );
  }

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'batch_form';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $queue = $this->queueFactory->get('my_custom_email');
    $number_of_items = $queue->numberOfItems();
    if (!empty($number_of_items)) {
      $form['help'] = [
        '#type' => 'markup',
        '#markup' => $this->t('Submitting this form will process the Manual Queue which contains @number items.', ['@number' => $number_of_items]),
      ];
      $form['actions']['#type'] = 'actions';
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Process queue'),
        '#button_type' => 'primary',
      ];
    }
    else {
      $form['help'] = [
        '#type' => 'markup',
        '#markup' => $this->t('Queue are empty. For using this form please create some node...'),
      ];
    }
    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->batchBuilder
      ->setTitle($this->t('Processing'))
      ->setInitMessage($this->t('Initializing.'))
      ->setProgressMessage($this->t('Completed @current of @total.'))
      ->setErrorMessage($this->t('An error has occurred.'));
    $this->batchBuilder->setFile(drupal_get_path('module', 'simple_batch') . '/src/Form/BatchForm.php');
    $this->batchBuilder->addOperation([$this, 'processItems'], []);
    $this->batchBuilder->setFinishCallback([$this, 'finished']);
    batch_set($this->batchBuilder->toArray());
  }

  /**
   * @param array $context
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function processItems(array &$context) {
    $queue = $this->queueFactory->get('my_custom_email');
    $queue_worker = $this->queueManager->createInstance('my_custom_email');
    $limit = 5;
    if (empty($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = $queue->numberOfItems();
    }
    // Only count that we need.
    for ($i = 0; $i < $limit; $i++) {
      if ($item = $queue->claimItem()) {
        try {
          $queue_worker->processItem($item->data);
          $queue->deleteItem($item);
          $context['sandbox']['progress']++;
          // Message during work.
          $context['message'] = $this->t('Now processing queue :progress of :count', [
            ':progress' => $context['sandbox']['progress'],
            ':count' => $context['sandbox']['max'],
          ]);
          // Need for final number.
          $context['results']['processed'] = $context['sandbox']['progress'];
        }
        catch (SuspendQueueException $e) {
          $queue->releaseItem($item);
          break;
        }
        catch (\Exception $e) {
          watchdog_exception('npq', $e);
        }
      }
    }
    if ($context['sandbox']['max'] == 0) {
      $context['finished'] = 1;
    }
    else {
      $context['finished'] = ($context['sandbox']['progress'] / $context['sandbox']['max']);
    }
  }

  /**
   * @param $success
   * @param $results
   * @param $operations
   */
  public function finished($success, $results, $operations) {
    $message = $this->t('Number of queue worked by batch: @count', [
      '@count' => $results['processed'],
    ]);

    $this->messenger()
      ->addStatus($message);
  }

}
