<?php

namespace Drupal\gatsby_fastbuilds\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an admin for for Gatsby fastbuilds.
 */
class GatsbyFastbuildsAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'gatsby_fastbuilds.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gatsby_fastbuilds_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('gatsby_fastbuilds.settings');
    $form['log_published'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Only log entities for published content'),
      '#description' => $this->t('Depending on your content workflow, you may only
        want fastbuilds to work for published content. By checking this box
        only published content entities will be logged and available for
        Fastbuilds.'),
      '#default_value' => $config->get('log_published') !== NULL ? $config->get('log_published') : FALSE,
      '#weight' => 0,
    ];
    $form['delete_log_entities'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete Old Gatsby Fastbuilds Log Entities'),
      '#description' => $this->t('Enable this to automatically clean up old
        Fastbuilds log entities on cron runs.'),
      '#default_value' => $config->get('delete_log_entities'),
      '#weight' => 1,
    ];
    $form['log_expiration'] = [
      '#type' => 'select',
      '#title' => $this->t('Fastbuilds Log Expiration'),
      '#description' => $this->t('How long do you want to store the Fastbuild
        log entities (after this time they will be automatically deleted and a
        full Gatsby rebuild will be required)?'),
      // Expiration values are stored in seconds.
      '#options' => [
        '604800' => $this->t('7 days'),
        '1209600' => $this->t('14 days'),
        '2592000' => $this->t('30 days'),
        '5184000' => $this->t('60 days'),
        '7776000' => $this->t('90 days'),
      ],
      '#default_value' => $config->get('log_expiration'),
      '#weight' => 2,
      '#states' => [
        'visible' => [
          ':input[name="delete_log_entities"]' => ['checked' => TRUE],
        ],
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('gatsby_fastbuilds.settings')
      ->set('log_published', $form_state->getValue('log_published'))
      ->set('delete_log_entities', $form_state->getValue('delete_log_entities'))
      ->set('log_expiration', $form_state->getValue('log_expiration'))
      ->save();
  }

}
