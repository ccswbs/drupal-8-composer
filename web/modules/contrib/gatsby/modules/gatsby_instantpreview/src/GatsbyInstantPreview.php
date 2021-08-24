<?php

namespace Drupal\gatsby_instantpreview;

use GuzzleHttp\ClientInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\gatsby\GatsbyPreview;
use Drupal\jsonapi_extras\EntityToJsonApi;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Defines a class to extend the default preview system with an instant preview.
 */
class GatsbyInstantPreview extends GatsbyPreview {

  /**
   * Drupal\jsonapi_extras\EntityToJsonApi definition.
   *
   * @var \Drupal\jsonapi_extras\EntityToJsonApi
   */
  private $entityToJsonApi;

  /**
   * Drupal\Core\Entity\EntityRepository definition.
   *
   * @var \Drupal\Core\Entity\EntityRepository
   */
  private $entityRepository;

  /**
   * Decorated service.
   *
   * @var \Drupal\gatsby\GatsbyPreview
   */
  private $innerService;

  /**
   * Constructs a new GatsbyInstantPreview object.
   */
  public function __construct(
    GatsbyPreview $inner_service,
    ClientInterface $http_client,
    ConfigFactoryInterface $config,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger,
    EntityToJsonApi $entity_to_json_api,
    EntityRepository $entity_repository
  ) {
    $this->innerService = $inner_service;
    $this->entityToJsonApi = $entity_to_json_api;
    $this->entityRepository = $entity_repository;
    parent::__construct($http_client, $config, $entity_type_manager, $logger);
  }

  /**
   * Prepares Gatsby Data to send to the preview and build servers.
   *
   * By preparing the data in a separate step we prevent multiple requests from
   * being sent to the preview or incremental builds servers if multiple
   * Drupal entities are update/inserted/deleted in a single request.
   */
  public function gatsbyPrepareData(ContentEntityInterface $entity = NULL, string $action = 'update') {
    $json = $this->getJson($entity);
    if (!$json) {
      return;
    }
    $json['id'] = $entity->uuid();
    $json['action'] = $action;

    // If there is a secret key, we decode the json, add the key, then encode.
    $settings = $this->configFactory->get('gatsby.settings');
    if ($settings->get('secret_key')) {
      $json['secret'] = $settings->get('secret_key');
    }

    $preview_url = $settings->get('preview_callback_url');
    if ($preview_url) {
      $json = $this->bundleData('preview', $preview_url, $json);
      $this->updateData('preview', $preview_url, $json);
    }

    $incrementalbuild_url = $settings->get('incrementalbuild_url');
    if (!$incrementalbuild_url) {
      return;
    }

    if ($settings->get('build_published')) {
      if (!($entity instanceof NodeInterface) || !$entity->isPublished()) {
        return;
      }

      if (empty($json['data']['relationships'])) {
        return;
      }

      // Generate JSON for all related entities to send to Gatsby.
      $entity_data = [];
      $included_types = $settings->get('preview_entity_types') ?: [];
      $this->buildRelationshipJson($json['data']['relationships'], $entity_data, array_values($included_types));

      if (!empty($entity_data)) {
        // Remove the uuid keys from the array.
        $entity_data = array_values($entity_data);

        $original_data = $json['data'];
        $entity_data[] = $original_data;
        $json['data'] = $entity_data;
      }
    }

    $json = $this->bundleData('incrementalbuild', $incrementalbuild_url, $json);
    $this->updateData('incrementalbuild', $incrementalbuild_url, $json);
  }

  /**
   * Triggers the refreshing of Gatsby preview and incremental builds.
   */
  public function gatsbyPrepareDelete(ContentEntityInterface $entity = NULL) {

    $json = [
      'id' => $entity->uuid(),
      'action' => 'delete',
      'type' => $entity->getEntityType()->id() . '--' . $entity->bundle(),
      'attributes' => [
        'langcode' => $entity->language()->getId(),
        'drupal_internal__revision_id' => $entity->getRevisionId(),
      ],
    ];

    $settings = $this->configFactory->get('gatsby.settings');

    // The id and action are needed in a data array for batch processing.
    $json['data'] = $json;

    // If there is a secret key, add the key to the request.
    if ($settings->get('secret_key')) {
      $json['secret'] = $settings->get('secret_key');
    }

    $preview_url = $settings->get('preview_callback_url');
    if ($preview_url) {
      $preview_json = $this->bundleData('preview', $preview_url, $json);
      $this->updateData('preview', $preview_url, $preview_json);
    }

    $incrementalbuild_url = $settings->get('incrementalbuild_url');
    if ($incrementalbuild_url) {
      $json = $this->bundleData('incrementalbuild', $incrementalbuild_url, $json);
      $this->updateData('incrementalbuild', $incrementalbuild_url, $json);
    }
  }

  /**
   * Bundles entity JSON data so it can be passed in a single request.
   */
  public function bundleData($key, $url, $json) {
    $updated = &self::$updateData;
    // The first time this method is called our updated data array is
    // empty so we can just return the data we were given.
    if (empty($updated)) {
      return $json;
    }

    if (!empty($updated[$key][$url]['json'])) {
      if (!empty($updated[$key][$url]['json']['data']['type'])) {
        // If there is only one entity, convert it to an array.
        $json['data'] = [$updated[$key][$url]['json']['data'], $json['data']];
      }
      else {
        // Check to make sure this entity wasn't already added.
        foreach ($updated[$key][$url]['json']['data'] as $index => $entity) {
          if ($entity['id'] == $json['id']) {
            // Update the entity data if this entity was already.
            $updated[$key][$url]['json']['data'][$index] = $json['data'];
            $json['data'] = $updated[$key][$url]['json']['data'];
            return $json;
          }
        }

        // Add new entities to the updated json data array.
        $updated[$key][$url]['json']['data'][] = $json['data'];
        // Update our json data array with the updated entities.
        $json['data'] = $updated[$key][$url]['json']['data'];
      }
    }

    return $json;
  }

  /**
   * Builds an array of entity JSON data based on entity relationships.
   */
  public function buildRelationshipJson($relationships, &$entity_data, $included_types = []) {
    foreach ($relationships as $data) {
      if (empty($data['data'])) {
        continue;
      }

      $related_items = $data['data'];

      // Check if this is a single value field.
      if (!empty($data['data']['type'])) {
        $related_items = [$data['data']];
      }

      foreach ($related_items as $related_data) {
        // Add JSON if the entity type is one that should be sent to Gatsby.
        $entityType = !empty($related_data['type']) ? explode('--', $related_data['type']) : "";
        if (!empty($entityType) && (!$included_types || in_array($entityType[0], $included_types, TRUE))) {
          // Skip this entity if it's already been added to the data array.
          if (!empty($entity_data[$related_data['id']])) {
            continue;
          }

          $related_entity = $this->entityRepository->loadEntityByUuid($entityType[0], $related_data['id']);

          // Make sure the related entity is a valid entity.
          if (empty($related_entity) || !($related_entity instanceof ContentEntityInterface)) {
            continue;
          }

          $related_json = $this->getJson($related_entity);
          if (!$related_json) {
            continue;
          }

          $entity_data[$related_data['id']] = $related_json['data'];
          // We need to traverse all related entities to get all relevant JSON.
          if (!empty($related_json['data']['relationships'])) {
            $this->buildRelationshipJson($related_json['data']['relationships'], $entity_data, $included_types);
          }
        }
      }
    }
  }

  /**
   * Gets the JSON object for an entity.
   */
  public function getJson(ContentEntityInterface $entity) {
    try {
      return $this->entityToJsonApi->normalize($entity);
    }
    catch (RouteNotFoundException $e) {
      return NULL;
    }
  }

}
