<?php

/**
 * @file
 * Contains gatsby_fastbuilds drush 8 commands.
 */

/**
 * Implements hook_drush_command().
 */
function gatsby_fastbuilds_drush_command() {

  $commands['gatsby-fastbuilds-delete'] = [
    'description' => 'Deletes all the Gatsby Fastbuilds Log entries.',
    'aliases' => ['gatsdel'],
    'examples' => [
      'drush gatsdel' => 'Deletes all the Gatsby Fastbuilds Log entries.',
    ],
  ];

  return $commands;
}

/**
 * Deletes all the Gatsby Fastbuilds log entries.
 */
function drush_gatsby_fastbuilds_delete() {
  $time = time();
  \Drupal::service('gatsby.gatsby_logger')
    ->deleteExpiredLoggedEntities($time);

  // Store the log time in order to validate future syncs.
  \Drupal::state()
    ->set('gatsby_fastbuilds.last_logtime', $time);
}
