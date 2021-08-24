<?php

namespace Drupal\Tests\gatsby\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Defines tests for getPath method in PathMapping.
 *
 * @group gatsby
 *
 * @covers \Drupal\gatsby\PathMapping
 */
class PathMappingTest extends KernelTestBase {

  use UserCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'system',
    'field',
    'node',
    'text',
    'filter',
    'gatsby',
    'path',
    'path_alias',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('system', ['sequences']);
    $this->installSchema('node', ['node_access']);
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installConfig(['field', 'node', 'text', 'filter', 'user']);
    $this->installEntitySchema('path_alias');
  }

  /**
   * Tests getPath method.
   */
  public function testGetPath() {
    $config_factory = \Drupal::configFactory();
    $config_factory->getEditable('gatsby.settings')->set('path_mapping', "/foo|/bar\n/biz|/bang")->save();
    $config_factory->getEditable('system.site')->set('page.front', '/hometest')->save(TRUE);
    $path = [
      '/hello' => '/hello',
      '/foo' => '/bar',
      '/hometest' => '',
    ];
    $owner = $this->createUser();
    foreach ($path as $drupal_path => $gatsby_path) {
      $node = $this->createNode([
        'title' => 'test',
        'type' => 'page',
        'uid' => $owner,
        'path' => ['alias' => $drupal_path],
      ]);
      $node->save();
      $this->assertEquals($gatsby_path, $this->container->get('gatsby.path_mapping')->getPath($node));
    }
  }

}
