<?php

namespace Drupal\gatsby;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Defines a class for generating Gatsby based previews.
 */
class GatsbyPreview {

  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Config Interface for accessing site configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Tracks data changes that should be sent to Gatsby.
   *
   * @var array
   */
  public static $updateData = [];

  /**
   * Constructs a new GatsbyPreview object.
   */
  public function __construct(
    ClientInterface $http_client,
    ConfigFactoryInterface $config,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger
  ) {
    $this->httpClient = $http_client;
    $this->configFactory = $config;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger->get('gatsby');
  }

  /**
   * Updates Gatsby Data array.
   */
  public function updateData($key, $url, $json = FALSE, $preview_path = "") {
    self::$updateData[$key][$url] = [
      'url' => $url,
      'json' => $json,
      'path' => $preview_path,
    ];
  }

  /**
   * Prepares Gatsby Data to send to the preview and build servers.
   *
   * By preparing the data in a separate step we prevent multiple requests from
   * being sent to the preview or incremental builds servers if mulutiple
   * Drupal entities are update/created/deleted in a single request.
   */
  public function gatsbyPrepareData(ContentEntityInterface $entity = NULL) {
    $settings = $this->configFactory->get('gatsby.settings');
    $preview_url = $settings->get('preview_callback_url');

    if ($preview_url) {
      $this->updateData('preview', $preview_url, FALSE);
    }

    $incrementalbuild_url = $settings->get('incrementalbuild_url');
    if (!$incrementalbuild_url) {
      return;
    }

    $build_published = $settings->get('build_published');
    if (!$build_published || ($entity instanceof NodeInterface && $entity->isPublished())) {
      $this->updateData('incrementalbuild', $incrementalbuild_url, FALSE);
    }
  }

  /**
   * Prepares Gatsby Deletes to send to the preview and build servers.
   *
   * This is a separate method to allow overriding services to override the
   * delete method to add additional data.
   */
  public function gatsbyPrepareDelete(ContentEntityInterface $entity = NULL) {
    $json = [
      'id' => $entity->uuid(),
      'action' => 'delete',
    ];

    $settings = $this->configFactory->get('gatsby.settings');
    $preview_url = $settings->get('preview_callback_url');
    if ($preview_url) {
      $this->updateData('preview', $preview_url, $json);
    }

    $incrementalbuild_url = $settings->get('incrementalbuild_url');
    if ($incrementalbuild_url) {
      $this->updateData('incrementalbuild', $incrementalbuild_url, $json);
    }
  }

  /**
   * Verify the entity is selected to sync to the Gatsby site.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   If the entity type should be sent to Gatsby Preview.
   */
  public function isPreviewEntity(ContentEntityInterface $entity) {
    $entityType = $entity->getEntityTypeId();
    $selectedEntityTypes = $this->configFactory->get('gatsby.settings')->get('preview_entity_types') ?: [];
    return in_array($entityType, array_values($selectedEntityTypes), TRUE);
  }

  /**
   * Returns true if a preview server or build server URL is configured.
   *
   * @return bool
   *   If a build or preview URL is configured.
   */
  public function isConfigured() {
    $settings = $this->configFactory->get('gatsby.settings');
    $preview_url = $settings->get('preview_callback_url');
    $incrementalbuild_url = $settings->get('incrementalbuild_url');

    if ($preview_url || $incrementalbuild_url) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Triggers the refreshing of Gatsby preview and incremental builds.
   */
  public function gatsbyUpdate() {
    foreach (self::$updateData as $urls) {
      foreach ($urls as $data) {
        $this->triggerRefresh($data['url'], $data['json'], $data['path']);
      }
    }

    // Reset update data to ensure it's only processed once.
    self::$updateData = [];
  }

  /**
   * Triggers Gatsby refresh endpoint.
   *
   * @param string $preview_callback_url
   *   The Gatsby URL to refresh.
   * @param object $json
   *   Optional JSON object to post to the server.
   * @param string $path
   *   The path used to trigger the refresh endpoint.
   */
  public function triggerRefresh($preview_callback_url, $json = FALSE, $path = "") {
    // If the URL has a comma it means multiple end points need to be called.
    if (stripos($preview_callback_url, ',')) {
      $urls = array_map('trim', explode(',', $preview_callback_url));

      foreach ($urls as $url) {
        $this->triggerRefresh($url, $json, $path);
      }

      return;
    }

    $data = ['timeout' => 1];

    if ($json) {
      $data['json'] = $json;
    }

    try {
      $this->httpClient->post($preview_callback_url . $path, $data);
    }
    catch (ConnectException $e) {
      // This is maintained for the legacy callback URL only.
      // Do nothing as no response is returned from the preview server.
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }

}
