<?php

namespace Drupal\build_hooks_azure\EventSubscriber;

use Drupal\build_hooks\Event\ResponseEvent;
use Drupal\Core\Cache\CacheBackendInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class TermPageAccessSubscriber. Subscribe to get cid event.
 *
 * @package Drupal\term_page_access
 */
class ResponseSubscriber implements EventSubscriberInterface {

  const CACHE_ID = 'build_hooks_azure:';

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
  public static function getSubscribedEvents() {
    $events[ResponseEvent::EVENT_NAME] = 'saveLastTrigger';
    return $events;
  }

  /**
   * Save the last trigger.
   *
   * @param \Drupal\build_hooks\Event\ResponseEvent $event
   *   The get cid event.
   */
  public function saveLastTrigger(ResponseEvent $event) {
    $response = $event->getResponse();
    $frontend = $event->getFrontendEnvironment();
    /* @var  $plugin  \Drupal\build_hooks\Plugin\FrontendEnvironmentInterface */
    $plugin = $frontend->getPlugin();
    if ($plugin->getPluginId() == 'azure') {
      $response = json_decode($response->getBody());
      $this->cache->set(self::CACHE_ID . $frontend->id(), $response);
    }

  }

}
