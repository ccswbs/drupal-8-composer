<?php

namespace Drupal\asset_bank\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;

class AssetBankForm extends ConfigFormBase
{
    /**
     * {@inheritdoc}.
     */
    public function getFormId()
    {
        return 'asset_bank_form';
    }


    /**
     * {@inheritdoc}.
     */
    protected function getEditableConfigNames() {
        return [
            'asset_bank.settings',
        ];
    }


    /**
     * {@inheritdoc}.
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        $form = parent::buildForm($form, $form_state);
        $config = $this->config('asset_bank.settings');

        $form['asset_bank_url'] = array(
            '#type' => 'textfield',
            '#attributes' => array(
                ' type' => 'url',
            ),
            '#title' => $this->t('Your Asset Bank URL:'),
            '#default_value' => $config->get('asset_bank.url'),
            '#description' => $this->t('Hint: for a free demo use <a>https://demo.assetbank-server.com/assetbank-standard</a>'),
            '#required' => TRUE,
        );

        $form['asset_bank_name'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Your Asset Bank Name:'),
            '#default_value' => $config->get('asset_bank.name'),
            '#description' => $this->t("The term used by the editor to reference your Asset Bank"),
            '#required' => TRUE,
        );

        $form['advanced'] = array(
            '#type' => 'details',
            '#title' => $this->t('Advanced Settings'),
            '#open' => TRUE,
        );

        $form['advanced']['asset_bank_repository_number'] = array(
            '#type' => 'textfield',
            '#attributes' => array(
                ' type' => 'number',
            ),
            '#title' => $this->t('Repository number:'),
            '#default_value' => $config->get('asset_bank.repository_number'),
            '#description' => $this->t('Optional: if you have multiple repositories configured in Asset Bank, use this setting to make Asset Bank store your CMS assets into a specific repository'),
            '#required' => FALSE,
        );

        $form['advanced']['asset_bank_subrepository_name'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Sub repository name:'),
            '#default_value' => $config->get('asset_bank.subrepository_name'),
            '#description' => $this->t('Optional: use this setting to make Asset Bank store your CMS assets into a specific folder inside your CMS repository'),
            '#required' => FALSE,
        );

        $form['advanced']['asset_bank_add_to_media_library'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Add assets to your Media Library?'),
            '#default_value' => $config->get('asset_bank.add_to_media_library'),
            '#description' => $this->t('Only works if the files will be served from this Drupal site (Note: the URL suffix in your Asset Bank must be a relative path, e.g. \'cms-return-url-suffix=?imageUrl=<strong>asset-bank</strong>\')'),
            '#required' => FALSE,
        );

        $form['advanced']['asset_bank_info'] = array(
            '#type' => 'item',
            '#title' => $this->t('&nbsp;'),
            '#description' => $this->t('For information on configuring your Asset Bank to use this plugin, please <a href="https://support.assetbank.co.uk" target="_blank">contact us</a>.'),
        );

        return $form;
    }


    /**
     * {@inheritdoc}.
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        if(!UrlHelper::isValid($form_state->getValue('asset_bank_url'), TRUE)){
            $form_state->setErrorByName('asset_bank_url', $this->t('The value you have entered does not appear to be a valid URL.'));
        }
    }


    /**
     * {@inheritdoc}.
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $config = $this->config('asset_bank.settings');
        $config->set('asset_bank.name', Html::escape($form_state->getValue('asset_bank_name')));
        $config->set('asset_bank.url', UrlHelper::stripDangerousProtocols(rtrim($form_state->getValue('asset_bank_url'), '/')));
        $config->set('asset_bank.repository_number', Html::escape($form_state->getValue('asset_bank_repository_number')));
        $config->set('asset_bank.subrepository_name', Html::escape($form_state->getValue('asset_bank_subrepository_name')));
        $config->set('asset_bank.add_to_media_library', Html::escape($form_state->getValue('asset_bank_add_to_media_library')));
        $config->save();
        return parent::submitForm($form, $form_state);
    }

}