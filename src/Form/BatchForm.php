<?php

namespace Drupal\simple_batch\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class BatchForm extends FormBase {



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
    $queue = \Drupal::queue('my_custom_email');
    if ($numberOfItems = $queue->numberOfItems()) {
      $form['info'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="info-form"><h3>This queue is already running.</h3></div>',
      ];
    }
    else {
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Run batch'),
      ];
    }
    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    //$queue = \Drupal::queue('my_custom_email');
  }

}
