<?php

namespace Drupal\gatsby_fastbuilds\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Defines a class for serving routes for Gatsby fastbuilds.
 */
class GatsbyFastbuildsController extends ControllerBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\gatsby_fastbuilds\GatsbyEntityLogger definition.
   *
   * @var \Drupal\gatsby_fastbuilds\GatsbyEntityLogger
   */
  protected $gatsbyEntityLogger;

  /**
   * Drupal\Core\State\State definition.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->gatsbyEntityLogger = $container->get('gatsby.gatsby_logger');
    $instance->state = $container->get('state');
    return $instance;
  }

  /**
   * Gatsby Fastbuilds sync callback to get incremental content changes.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   Returns a JsonResponse with all of the content changes since last fetch.
   */
  public function sync($last_fetch) {
    $last_logtime = $this->state->get('gatsby_fastbuilds.last_logtime', 0);

    $sync_data = [
      'status' => -1,
      'timestamp' => time(),
    ];

    if (!empty($last_fetch) && $last_fetch >= $last_logtime) {
      // Get all of the sync entities.
      $sync_data = $this->gatsbyEntityLogger->getSync($last_fetch);
    }

    return new JsonResponse($sync_data);
  }

}
