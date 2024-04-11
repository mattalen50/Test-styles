<?php

namespace Drupal\umn_livewhale_event_importer\Form;

use Drupal;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a confirmation form to confirm deletion of something by id.
 */
class ConfirmDeleteForm extends ConfirmFormBase {

  /**
   * ID of the item to delete.
   *
   * @var int
   */
  protected $id;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $id = NULL) {
    $this->id = $id;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->id == 1) {
      // Delete queues.
      $queuedel = Drupal::queue('umn_livewhale_event_importer_queue_1');
      $queuedel->deleteQueue();
      $queuedel2 = Drupal::queue('umn_livewhale_event_importer_queue_2');
      $queuedel2->deleteQueue();
      // Reset state so that you can run a migration again.
      Drupal::state()->set('umn_livewhale_event_importer_is_running', FALSE);
      $form_state->setRedirect('umn_livewhale_event_importer.description');
      // Add confirmation message.
      Drupal::messenger()
        ->addMessage($this->t('Deleted all queues'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "confirm_delete_form";
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('umn_livewhale_event_importer.description');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $names = [
      1 => 'all queues',
    ];
    $args = [
      '%id' => $names[$this->id],
    ];
    return $this->t('Do you want to delete %id?', $args);
  }

}
