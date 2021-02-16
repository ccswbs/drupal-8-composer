<?php

namespace Drupal\build_hooks_azure\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class BuildHooksCircleCiConfigForm.
 */
class AzureConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'build_hooks_azure.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'build_hook_azure_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('build_hooks_azure.settings');

    $form['organization'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Organization'),
      '#description' => $this->t('The name of the Azure DevOps organization.'),
      '#required' => TRUE,
      '#default_value' => $config->get('organization'),
    ];
    $form['project'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Project'),
      '#description' => $this->t('Project ID or project name'),
      '#required' => TRUE,
      '#default_value' => $config->get('project'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Save the api key to configuration:
    $this->config('build_hooks_azure.settings')
      ->set('organization', $form_state->getValue('organization'))
      ->set('project', $form_state->getValue('project'))
      ->save();
  }

}
