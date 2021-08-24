<?php

namespace Drupal\gatsby_instantpreview\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\gatsby\Form\GatsbyAdminForm;

/**
 * Class GatsbyAdminForm.
 */
class GatsbyInstantPreviewAdminForm extends GatsbyAdminForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('gatsby.settings');

    $form['secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Gatsby Secret Key'),
      '#description' => $this->t('A Secret Key value that will be sent to Gatsby Preview and Build servers for an
        additional layer of security. <a href="#" id="gatsby--generate">Generate a Secret Key</a>'),
      '#default_value' => $config->get('secret_key'),
      '#weight' => 5,
    ];

    $form['additional_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Additional JSON:API Settings'),
      '#weight' => 6,
    ];

    $form['#attached']['library'][] = 'gatsby/gatsby_admin';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('gatsby.settings')
      ->set('secret_key', $form_state->getValue('secret_key'))
      ->save();
  }

}
