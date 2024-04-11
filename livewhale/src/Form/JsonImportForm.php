<?php

namespace Drupal\umn_livewhale_event_importer\Form;

use Drupal;
use stdClass;
use Exception;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\CronInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form showing auto and manual imports using cron.
 */
class JsonImportForm extends ConfigFormBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The cron service.
   *
   * @var \Drupal\Core\CronInterface
   */
  protected $cron;

  /**
   * The queue object.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queue;

  /**
   * The queue object.
   *
   * @var \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  protected $queueManager;

  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountInterface $current_user, CronInterface $cron, QueueFactory $queue, QueueWorkerManagerInterface $queue_manager, StateInterface $state) {
    parent::__construct($config_factory);
    $this->currentUser = $current_user;
    $this->cron = $cron;
    $this->queue = $queue;
    $this->queueManager = $queue_manager;
    $this->state = $state;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = new static(
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('cron'),
      $container->get('queue'),
      $container->get('plugin.manager.queue_worker'),
      $container->get('state')
    );
    $form->setMessenger($container->get('messenger'));
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'umn_livewhale_event_importer';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('umn_livewhale_event_importer.settings');

    $form['status'] = [
      '#type' => 'details',
      '#title' => $this->t('UMN LiveWhale Event Importer information'),
      '#open' => TRUE,
    ];
    $form['status']['intro'] = [
      '#type' => 'item',
      '#markup' => $this->t('This module sets the automatic and manual importing of LiveWhale JSON event data.'),
    ];

    $next_execution = $this->state->get('umn_livewhale_event_importer.next_execution');
    $next_execution = !empty($next_execution) ? $next_execution : Drupal::time()->getRequestTime();

    $args = [
      '%time' => date('r', $next_execution),
      '%seconds' => $next_execution - Drupal::time()->getRequestTime(),
    ];
    $form['status']['last'] = [
      '#type' => 'item',
      '#markup' => $this->t('Next Execution: The next event import will next execute the first time cron runs after %time (%seconds seconds from now)', $args),
    ];
    $form['cron_json_setup'] = [
        '#type' => 'details',
        '#title' => $this->t('JSON events feed URL'),
        '#open' => TRUE,
      ];
    // Textfield.
    $form['cron_json_setup']['jsonurl'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $config->get('json'),
      '#size' => 60,
      '#maxlength' => 255,
      '#description' => $this->t('Please enter your JSON feed URL from https://events.tc.umn.edu/feed_builder'),
    ];

    $form['configuration'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuration of LiveWhale event automatic importing'),
      '#open' => TRUE,
    ];
    $form['configuration']['umn_livewhale_event_importer_interval'] = [
      '#type' => 'select',
      '#title' => $this->t('Set LiveWhale event import interval'),
      '#description' => $this->t('The time after which LiveWhale events will automatically be imported.'),
      '#default_value' => $config->get('interval'),
      '#options' => [
        86400 => $this->t('1 day'),
        172800 => $this->t('2 days'),
        259200 => $this->t('3 days'),
        604800 => $this->t('1 week'),
        1209600 => $this->t('2 weeks'),
        2419200 => $this->t('28 days'),
      ],
    ];

    $form['import_json_setup'] = [
        '#type' => 'details',
        '#title' => $this->t('Run an events import now'),
        '#open' => TRUE,
    ];
    $form['import_json_setup']['actions'] = ['#type' => 'actions'];
    $form['import_json_setup']['actions']['submit2'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start Event Import'),
      '#submit' => [[$this, 'cronJsonRun']],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Allow user to directly execute cron, optionally forcing it.
   */
  public function cronJsonRun(array &$form, FormStateInterface &$form_state) {
    // Reset time for next execution
    $this->state->set('umn_livewhale_event_importer.next_execution', 0);
    // Use a state variable to signal that cron was run manually from this form.
    $this->state->set('umn_livewhale_event_importer_show_status_message', TRUE);
    $json_url = $form_state->getValue('jsonurl');
    if (empty($json_url)) {
      $this->messenger()->addError($this->t('Please enter a JSON URL.'));
      $form_state->setRedirect('umn_livewhale_event_importer.description');
    }
    if ($this->cron->run()) {
      $this->messenger()->addMessage($this->t('Cron ran successfully.'));
      $form_state->setRedirect('umn_livewhale_event_importer.description');
    }
    else {
      $this->messenger()->addError($this->t('Cron run failed.'));
    }
  }

  /**
   * Button to delete queues.
   */
  function deleteItems(array &$form, FormStateInterface &$form_state) {
    $form_state->setRedirect('umn_livewhale_event_importer.delete', array('id' => 1));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('umn_livewhale_event_importer.settings')
      ->set('interval', $form_state->getValue('umn_livewhale_event_importer_interval'))
      ->save();
    Drupal::state()
      ->set('umn_livewhale_event_importer.next_execution', Drupal::time()->getRequestTime() + $form_state->getValue('umn_livewhale_event_importer_interval'));
    // Pass JSON url into settings
    $this->config('umn_livewhale_event_importer.settings')
      ->set('json', $form_state->getValue('jsonurl'))
      ->save();
    Drupal::state()
      ->set('umn_livewhale_event_importer.migration_source_uri', $form_state->getValue('jsonurl'));
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['umn_livewhale_event_importer.settings'];
  }

}
