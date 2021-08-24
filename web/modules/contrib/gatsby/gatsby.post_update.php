<?php

/**
 * @file
 * Contains post update hooks.
 */

use Drupal\node\Entity\NodeType;

/**
 * Migrates preview settings.
 */
function gatsby_post_update_migrate_preview_settings() {
  $gatsby_settings = \Drupal::service('config.factory')->getEditable('gatsby.settings');
  $preview_settings = $gatsby_settings->get('preview');
  $target_settings = $gatsby_settings->get('target');
  $iframe_settings = $gatsby_settings->get('iframe');

  foreach ($preview_settings as $node_type_id => $setting) {
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = NodeType::load($node_type_id);
    $node_type->setThirdPartySetting('gatsby', 'preview', (bool) $setting);
    $node_type->setThirdPartySetting('gatsby', 'iframe', (bool) $iframe_settings[$node_type_id]);
    $node_type->setThirdPartySetting('gatsby', 'target', $target_settings[$node_type_id]);
    $node_type->save();
  }

  $gatsby_settings->clear('preview')->clear('target')->clear('iframe')->save();
}

/**
 * Populates preview_callback_url.
 */
function gatsby_post_update_set_preview_callback_url() {
  $gatsby_settings = \Drupal::service('config.factory')->getEditable('gatsby.settings');
  $server_urls = array_map('trim', explode(',', \Drupal::config('gatsby.settings')->get('server_url')));
  $server_url = reset($server_urls);
  $gatsby_settings->set('preview_callback_url', sprintf('%s/__refresh', rtrim($server_url, '/')));
  $gatsby_settings->save();
}
