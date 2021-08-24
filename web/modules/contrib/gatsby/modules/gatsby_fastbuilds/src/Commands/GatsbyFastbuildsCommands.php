<?php

namespace Drupal\gatsby_fastbuilds\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\State\StateInterface;
use Drupal\gatsby_fastbuilds\GatsbyEntityLogger;

/**
 * A drush command file.
 *
 * @package Drupal\gatsby_fastbuilds\Commands
 */
class GatsbyFastbuildsCommands extends DrushCommands {

  /**
   * Drupal\gatsby_fastbuilds\GatsbyEntityLogger definition.
   *
   * @var \Drupal\gatsby_fastbuilds\GatsbyEntityLogger
   */
  protected $gatsbyLogger;

  /**
   * Drupal\Core\State\StateInterface definition.
   *
   * @var \Drupal\State\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new GatsbyFastbuildsCommands object.
   */
  public function __construct(
      GatsbyEntityLogger $gatsby_logger,
      StateInterface $state) {

    parent::__construct();
    $this->gatsbyLogger = $gatsby_logger;
    $this->state = $state;
  }

  /**
   * Drush command that deletes all the Gatsby Fastbuilds Log entries.
   *
   * @command gatsby_fastbuilds:delete
   * @aliases gatsdel
   * @usage gatsby_fastbuilds:delete
   */
  public function delete() {
    $time = time();
    $this->gatsbyLogger->deleteExpiredLoggedEntities($time);

    // Store the log time in order to validate future syncs.
    $this->state->set('gatsby_fastbuilds.last_logtime', $time);
  }

}
