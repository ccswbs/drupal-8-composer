<?php

namespace Drupal\Tests\gatsby_fastbuilds\Kernel;

use Drupal\Core\Url;
use Drupal\jsonapi_extras\Entity\JsonapiResourceConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Defines a test for fastbuilds with disabled node-types.
 *
 * @group gatsby_fastbuilds
 *
 * @requires module jsonapi_extras
 */
class DisabledNodeTypesTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   *
   * @todo Remove in https://www.drupal.org/project/gatsby/issues/3198678
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'gatsby_instantpreview',
    'node',
    'gatsby_fastbuilds',
    'gatsby',
    'jsonapi',
    'serialization',
    'jsonapi_extras',
    'field',
    'text',
    'path_alias',
    'options',
    'system',
    'user',
    'filter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();
    $this->installConfig(['node', 'filter']);
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('path_alias');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->createContentType(['type' => 'page']);
    $this->createContentType(['type' => 'article']);
    $this->installEntitySchema('gatsby_log_entity');
    $config_factory = \Drupal::configFactory();
    $config_factory->getEditable('gatsby.settings')
      ->set('preview_entity_types', [
        'node',
      ])
      ->set('server_url', 'http://example.com')
      ->save();
    $config_factory->getEditable('gatsby_fastbuilds.settings')->set('log_published', TRUE)->save();
    $this->setUpCurrentUser([], [
      'access content',
    ]);
    $disabled_article = JsonapiResourceConfig::create([
      'id' => 'node--article',
      'status' => TRUE,
      'disabled' => TRUE,
      'path' => 'node/article',
      'resourceType' => 'node--article',
    ]);
    $disabled_article->save();
    \Drupal::service('jsonapi.resource_type.repository')->reset();
    \Drupal::service('cache_tags.invalidator')->invalidateTags(['jsonapi_resource_types']);
    \Drupal::service('router.builder')->rebuild();
    try {
      Url::fromRoute('jsonapi.node--article.individual')->toString();
      $this->fail('Route should be disabled');
    }
    catch (RouteNotFoundException $e) {
      $this->addToAssertionCount(1);
    }
  }

  /**
   * Tests entity insert.
   */
  public function testEntityInsertWithDisabledResource() {
    $entity = Node::create([
      'type' => 'article',
      'status' => 1,
      'title' => 'foo',
    ]);
    $entity->save();
  }

}
