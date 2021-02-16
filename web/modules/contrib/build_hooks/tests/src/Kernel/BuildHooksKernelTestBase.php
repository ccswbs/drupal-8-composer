<?php

namespace Drupal\Tests\build_hooks\Kernel;

use Drupal\build_hooks\Entity\FrontendEnvironment;
use Drupal\build_hooks\TriggerInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * Defines a base kernel test class for Build Hooks module.
 */
abstract class BuildHooksKernelTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'build_hooks',
    'views',
    'build_hooks_test',
    'system',
    'user',
    'entity_test',
    'dynamic_entity_reference',
  ];

  /**
   * History of requests.
   *
   * @var \GuzzleHttp\Psr7\Request[]
   */
  protected $history = [];

  /**
   * Mock client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $mockClient;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig('build_hooks');
    $this->installConfig('system');
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('build_hooks_deployment');
    $this->config('build_hooks.settings')->set('logging.entity_types', ['entity_test' => 'entity_test'])->save();
  }

  /**
   * Mocks the http-client.
   */
  protected function mockClient(Response ...$responses) {
    if (!isset($this->mockClient)) {
      // Create a mock and queue two responses.
      $mock = new MockHandler($responses);

      $handler_stack = HandlerStack::create($mock);
      $history = Middleware::history($this->history);
      $handler_stack->push($history);
      $this->mockClient = new Client(['handler' => $handler_stack]);
    }
    $this->container->set('http_client', $this->mockClient);
  }

  /**
   * Assert a front-end environment build hook fires.
   *
   * @param string $plugin
   *   Build hook plugin.
   * @param string $deployment_strategy
   *   Deployment strategy.
   * @param string $expected_url
   *   Expected URL.
   * @param array $settings
   *   Plugin settings.
   *
   * @return \GuzzleHttp\Psr7\Request
   *   Build hook request.
   */
  protected function assertFrontendEnvironmentBuildHook(
    string $plugin,
    string $deployment_strategy,
    string $expected_url,
    array $settings
  ) : Request {
    $this->mockClient(new Response(200, [], 'Hello, World'));
    $title = $this->randomMachineName();
    $environment = FrontendEnvironment::create([
      'id' => 'foo',
      'label' => $title,
      'settings' => $settings,
      'plugin' => $plugin,
      'deployment_strategy' => $deployment_strategy,
    ]);
    $environment->save();
    $this->assertEquals($title, $environment->label());
    $entity = EntityTest::create([
      'name' => $this->randomMachineName(),
    ]);
    $entity->save();
    /** @var \Drupal\build_hooks\DeployLogger $logger */
    $logger = \Drupal::service('build_hooks.deploylogger');
    // The entity-save strategy will never have queued items.
    $this->assertEquals($deployment_strategy === TriggerInterface::DEPLOYMENT_STRATEGY_ENTITYSAVE ? 0 : 1, $logger->getNumberOfItemsSinceLastDeploymentForEnvironment($environment));
    if ($deployment_strategy === TriggerInterface::DEPLOYMENT_STRATEGY_CRON) {
      \Drupal::service('cron')->run();
    }
    if ($deployment_strategy === TriggerInterface::DEPLOYMENT_STRATEGY_MANUAL) {
      \Drupal::service('build_hooks.trigger')->triggerBuildHookForEnvironment($environment);
    }
    $this->assertCount(1, $this->history);
    $request = reset($this->history)['request'];
    $this->assertEquals($expected_url, (string) $request->getUri());
    $this->assertEquals(0, $logger->getNumberOfItemsSinceLastDeploymentForEnvironment($environment));
    return $request;
  }

}
