<?php

namespace Drupal\build_hooks_azure\Controller;

use Drupal\build_hooks\Entity\FrontendEnvironmentInterface;
use Drupal\build_hooks_azure\EventSubscriber\ResponseSubscriber;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller to generate list of channels URLs.
 */
class LastTrigger extends ControllerBase {


  /**
   * A path alias manager instance.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $cache;

  /**
   * {@inheritdoc}
   */
  public function __construct(CacheBackendInterface $cache) {
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cache.default')
    );
  }

  /**
   * Get lest detail of trigger build hooks azure.
   *
   * @param \Drupal\build_hooks\Entity\FrontendEnvironmentInterface $frontend_environment
   *   The front end to get the detail.
   *
   * @return array
   *   Render array.
   */
  public function detail(FrontendEnvironmentInterface $frontend_environment) {

    $cid = ResponseSubscriber::CACHE_ID . $frontend_environment->id();
    $cached = $this->cache->get($cid);
    if (empty($cached->data)) {
      return [
        '#type' => 'markup',
        '#markup' => $this->t('No deploy found'),
      ];
    }
    $items = [];
    foreach ($cached->data as $field_name => $value) {
      if (!is_array($value) && !is_object($value)) {
        $items[] = [
          'dt' => ['value' => $field_name],
          'dd' => ['value' => $value],
        ];
      }
    }
    return [
      '#theme' => 'build_hooks_azure_description_list',
      '#items' => $items,
    ];

  }

}
