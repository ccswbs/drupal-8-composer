<?php

namespace Drupal\Tests\gatsby_fastbuilds\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Defines a test for the GatsbyEntityLogger.
 *
 * @group gatsby_fastbuilds
 *
 * @requires module jsonapi_extras
 */
class GatsbyEntityLoggerTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use UserCreationTrait;
  use ContentModerationTestTrait;

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
    'path_alias',
    'jsonapi',
    'serialization',
    'jsonapi_extras',
    'field',
    'text',
    'options',
    'system',
    'user',
    'filter',
    'workflows',
    'content_moderation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();
    $this->installConfig(['node', 'filter']);
    $this->installSchema('system', ['sequences']);
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('path_alias');
    $this->installEntitySchema('user');
    $this->createContentType(['type' => 'page']);
    $this->installEntitySchema('gatsby_log_entity');
    $config_factory = \Drupal::configFactory();
    $config_factory->getEditable('gatsby.settings')->set('preview_entity_types', [
      'node',
    ])->save();
    $config_factory->getEditable('gatsby_fastbuilds.settings')->set('log_published', TRUE)->save();
    $this->setUpCurrentUser([], [
      'access content',
    ]);
    $this->installEntitySchema('content_moderation_state');
    $workflow = $this->createEditorialWorkflow();
    $this->addEntityTypeAndBundleToWorkflow($workflow, 'node', 'page');
  }

  /**
   * Tests entity insert.
   */
  public function testEntityInsert() {
    $log_storage = \Drupal::entityTypeManager()->getStorage('gatsby_log_entity');
    $this->assertCount(0, $log_storage->loadMultiple());

    $entity = Node::create([
      'type' => 'page',
      'status' => 1,
      'title' => 'foo',
      'moderation_state' => 'published',
    ]);
    $entity->save();
    $logs = $log_storage->loadMultiple();
    $this->assertCount(1, $logs);
    $log = reset($logs);
    $this->assertEquals('insert', $log->action->value);
    $this->assertEquals('node', $log->entity->value);
    $this->assertEquals('page', $log->bundle->value);
    $this->assertEquals($entity->uuid(), $log->entity_uuid->value);

    $entity->status = 0;
    $entity->moderation_state = 'draft';
    $entity->setNewRevision();
    $entity->save();

    $log_storage->resetCache();
    $logs = $log_storage->loadMultiple();
    $this->assertCount(1, $logs);
    $log = reset($logs);
    $this->assertEquals('insert', $log->action->value);
    $this->assertEquals('node', $log->entity->value);
    $this->assertEquals('page', $log->bundle->value);
    $this->assertEquals($entity->uuid(), $log->entity_uuid->value);
  }

}
