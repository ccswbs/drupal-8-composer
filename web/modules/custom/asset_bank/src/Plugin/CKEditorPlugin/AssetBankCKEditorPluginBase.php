<?php

namespace Drupal\asset_bank\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 *
 * The annotation CKEditorPlugin tells Drupal
 * there is a plugin for CKEditor to load.
 * For the id, use the name of the plugin
 * as defined in the plugin.js file
 *
 * \Drupal\ckeditor\CKEditorPluginBase provides a default implementation
 * so CKEditor plugins don't need to implement every method.
 * Which means it must optimize for the most common case:
 * it is only useful for plugins that provide buttons.
 *
 * @CKEditorPlugin(
 *   id = "asset_bank",
 *   label = @Translation("Asset Bank Button")
 * )
 */
class AssetBankCKEditorPluginBase extends CKEditorPluginBase {

    private function getPluginPath(){
        return drupal_get_path('module', 'asset_bank') . '/src/Plugin/CKEditorPlugin';
    }

    /**
     * Implements \Drupal\ckeditor\Plugin\CKEditorPluginButtonsInterface::getButtons()
     * {@inheritdoc}
     */
    public function getButtons() {
        return [
            'AssetBank' => [
                'label' => t('Asset Bank Button'),
                'image' => self::getPluginPath() . '/icons/logo.png'
            ]
        ];
    }

    /**
     * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getFile()
     * {@inheritdoc}
     */
    public function getFile() {
        return self::getPluginPath() . '/plugin.js';
    }

    /**
     * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::isInternal()
     * {@inheritdoc}
     */
    public function isInternal() {
        return FALSE;
    }

    /**
     * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getConfig()
     * {@inheritdoc}
     */
    public function getConfig(Editor $editor) {
        $config = \Drupal::config('asset_bank.settings');
        return [
            'asset_bank' => [
                'url' => $config->get('asset_bank.url'),
                'name' => $config->get('asset_bank.name'),
                'repository_number' => $config->get('asset_bank.repository_number'),
                'subrepository_name' => $config->get('asset_bank.subrepository_name'),
                'callback_url' => urlencode($this->getCallbackUrl()),
            ]
        ];
    }

    private function getCallbackUrl() {
        $base_url = $GLOBALS['base_url'];
        $assetBankCallback = \Drupal::urlGenerator()->generate('asset_bank.callback');
        if (strpos($assetBankCallback, $base_url) === 0) {
            return $assetBankCallback;
        } else {
            return $base_url . $assetBankCallback;
        }
    }

    /**
     * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getDependencies()
     * {@inheritdoc}
     */
    function getDependencies(Editor $editor) {
        return array();
    }


    /**
     * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getLibraries()
     * {@inheritdoc}
     */
    function getLibraries(Editor $editor) {
        return array();
    }

}