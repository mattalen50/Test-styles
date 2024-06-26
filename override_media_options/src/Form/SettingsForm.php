<?php

namespace Drupal\override_media_options\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements SettingsForm().
 *
 * SettingForm() function Extends functionalities of ConfigFormBase().
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['override_media_options.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'override_media_options_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('override_media_options.settings');

    $form['general_permissions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('General permissions, across all media types'),
      '#default_value' => $config->get('general_permissions'),
    ];

    $form['specific_permissions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Specific permissions, for each individual media type'),
      '#default_value' => $config->get('specific_permissions'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('override_media_options.settings')
      ->set('general_permissions', $form_state->getValue('general_permissions'))
      ->set('specific_permissions', $form_state->getValue('specific_permissions'))
      ->save(TRUE);
  }

}
