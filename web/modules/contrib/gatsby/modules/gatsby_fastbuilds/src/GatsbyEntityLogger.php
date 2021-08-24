<?php

namespace Drupal\gatsby_fastbuilds;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\gatsby_fastbuilds\Entity\GatsbyLogEntityInterface;
use Drupal\gatsby_instantpreview\GatsbyInstantPreview;

/**
 * Defines a service for logging content entity changes using log entities.
 */
class GatsbyEntityLogger {

  /**
   * Config Interface for accessing site configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\gatsby_instantpreview\GatsbyInstantPreview definition.
   *
   * @var \Drupal\gatsby_instantpreview\GatsbyInstantPreview
   */
  protected $gatsbyInstantPreview;

  /**
   * Constructs a new GatsbyEntityLogger object.
   */
  public function __construct(ConfigFactoryInterface $config,
      EntityTypeManagerInterface $entity_type_manager,
      GatsbyInstantPreview $gatsbyInstantPreview) {
    $this->config = $config->get('gatsby.settings');
    $this->entityTypeManager = $entity_type_manager;
    $this->gatsbyInstantPreview = $gatsbyInstantPreview;
  }

  /**
   * Logs an entity create, update, or delete.
   *
   * @param Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to log the details for.
   * @param string $action
   *   The action for this entity (insert, update, or delete).
   */
  public function logEntity(ContentEntityInterface $entity, string $action) {
    $this->deleteLoggedEntity($entity->uuid(), $entity->language()->getId());

    $json = [];
    if ($action !== 'delete') {
      $json = $this->gatsbyInstantPreview->getJson($entity);
      if (!$json) {
        return;
      }
    }

    $json['id'] = $entity->uuid();
    $json['action'] = $action;
    $json['langcode'] = $entity->language()->getId();

    $log_entry = [
      'entity_uuid' => $entity->uuid(),
      'title' => $entity->label(),
      'entity' => $entity->getEntityTypeId(),
      'bundle' => $entity->bundle(),
      'langcode' => $entity->language()->getId(),
      'action' => $action,
      'json' => json_encode($json),
    ];

    $log = $this->entityTypeManager->getStorage('gatsby_log_entity')
      ->create($log_entry);
    $log->save();
  }

  /**
   * Logs an entity create/update/delete and includes all related entity data.
   *
   * @param Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to log the details for.
   * @param string $action
   *   The action for this entity (insert, update, or delete).
   */
  public function logEntityWithRelationships(ContentEntityInterface $entity, string $action) {
    $this->deleteLoggedEntity($entity->uuid(), $entity->language()->getId());

    $json = [];
    if ($action !== 'delete') {
      $json = $this->gatsbyInstantPreview->getJson($entity);
      if (!$json) {
        return;
      }

      if (!empty($json['data']['relationships'])) {
        // Generate JSON for all related entities to send to Gatsby.
        $entity_data = [];
        $this->gatsbyInstantPreview->buildRelationshipJson($json['data']['relationships'], $entity_data);

        if (!empty($entity_data)) {
          // Remove the uuid keys from the array.
          $entity_data = array_values($entity_data);

          $original_data = $json['data'];
          $entity_data[] = $original_data;
          $json['data'] = $entity_data;
        }
      }
    }

    $json['id'] = $entity->uuid();
    $json['action'] = $action;

    $log_entry = [
      'entity_uuid' => $entity->uuid(),
      'title' => $entity->label(),
      'entity' => $entity->getEntityTypeId(),
      'bundle' => $entity->bundle(),
      'langcode' => $entity->language()->getId(),
      'action' => $action,
      'json' => json_encode($json),
    ];

    $log = $this->entityTypeManager->getStorage('gatsby_log_entity')
      ->create($log_entry);
    $log->save();
  }

  /**
   * Deletes existing entities based on uuid.
   *
   * @param string $uuid
   *   The entity uuid to delete the log entries for.
   * @param string $langcode
   *   The entity langcode.
   */
  public function deleteLoggedEntity($uuid, $langcode = 'en') {
    $query = $this->entityTypeManager->getStorage('gatsby_log_entity')->getQuery()->accessCheck(FALSE);
    $entity_uuids = $query->condition('entity_uuid', $uuid)->condition('langcode', $langcode)->execute();
    $entities = $this->entityTypeManager->getStorage('gatsby_log_entity')->loadMultiple($entity_uuids);

    foreach ($entities as $entity) {
      $entity->delete();
    }
  }

  /**
   * Deletes old or expired existing logged entities based on timestamp.
   *
   * @param int $timestamp
   *   The entity uuid to delete the log entries for.
   */
  public function deleteExpiredLoggedEntities($timestamp) {
    $query = $this->entityTypeManager->getStorage('gatsby_log_entity')->getQuery()->accessCheck(FALSE);
    $entity_uuids = $query->condition('created', $timestamp, '<')->execute();
    $entities = $this->entityTypeManager->getStorage('gatsby_log_entity')
      ->loadMultiple($entity_uuids);

    foreach ($entities as $entity) {
      $entity->delete();
    }
  }

  /**
   * Get the oldest created timestampe for a logged entity.
   */
  public function getOldestLoggedEntityTimestamp() {
    $query = $this->entityTypeManager->getStorage('gatsby_log_entity')->getQuery()->accessCheck(FALSE);
    $entity_uuids = $query->sort('created')->range(0, 1)->execute();
    $entities = $this->entityTypeManager->getStorage('gatsby_log_entity')
      ->loadMultiple($entity_uuids);

    if (!empty($entities)) {
      $entity = array_pop($entities);
      return $entity->getCreatedTime();
    }

    return FALSE;
  }

  /**
   * Gets log entities for a sync based on last fetched timestamp.
   *
   * @param int $last_fetch
   *   The time the sync was last fetched.
   *
   * @return array
   *   The JSON data for the entities to sync.
   */
  public function getSync($last_fetch) {
    $query = $this->entityTypeManager->getStorage('gatsby_log_entity')->getQuery()->accessCheck(FALSE);
    $entity_uuids = $query->condition('created', $last_fetch, '>')
      ->sort('created')->execute();

    $entities = $this->entityTypeManager->getStorage('gatsby_log_entity')
      ->loadMultiple($entity_uuids);

    $sync_data = [
      'timestamp' => time(),
      'entities' => [],
    ];

    foreach ($entities as $entity) {
      if ($entity instanceof GatsbyLogEntityInterface) {
        $sync_data['timestamp'] = $entity->getCreatedTime();
        $sync_data['entities'][] = json_decode($entity->get('json')->value);
      }
    }

    return $sync_data;
  }

}
