<?php

namespace Drupal\build_hooks\Event;

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\build_hooks\Entity\FrontendEnvironmentInterface;

/**
 * Class ResponseEvent. Event fired when a build event gets the response.
 *
 * @package Drupal\build_hooks\Event
 */
class ResponseEvent extends Event {


  const EVENT_NAME = 'build_hooks.response';

  /**
   * The http client response.
   *
   * @var \Psr\Http\Message\ResponseInterface
   */
  protected $response;

  /**
   * The build hook plugin.
   *
   * @var \Drupal\build_hooks\Plugin\FrontendEnvironmentInterface
   */
  protected $plugin;

  /**
   * Set the response.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   Response.
   */
  public function setResponse(ResponseInterface $response) {
    $this->response = $response;
  }

  /**
   * Get the response.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The http client $response.
   */
  public function getResponse() {
    return $this->response;
  }

  /**
   * Set the plugin.
   *
   * @param \Drupal\build_hooks\Plugin\FrontendEnvironmentInterface $plugin
   *   The build hook plugin.
   */
  public function setPlugin(FrontendEnvironmentInterface $plugin) {
    $this->plugin = $plugin;
  }

  /**
   * Get the plugin.
   *
   * @return \Drupal\build_hooks\Plugin\FrontendEnvironmentInterface
   *   The build hook plugin.
   */
  public function getPlugin() {
    return $this->frontendEnvironment->getPlugin();
  }

  /**
   * ResponseEvent constructor.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The http client $response.
   * @param \Drupal\build_hooks\Entity\FrontendEnvironmentInterface $frontend_environment
   *   The frontend environment entity.
   */
  public function __construct(ResponseInterface $response, FrontendEnvironmentInterface $frontend_environment) {
    $this->response = $response;
    $this->frontendEnvironment = $frontend_environment;
  }

}

