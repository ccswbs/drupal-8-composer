<?php

namespace Drupal\Tests\gatsby_instantpreview\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Defines a test for the GatsbyInstantPreview.
 *
 * @group gatsby_instantpreview
 *
 * @requires module jsonapi_extras
 */
class GatsbyInstantPreviewTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use UserCreationTrait;
  use EntityReferenceTestTrait;

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
    'gatsby',
    'jsonapi',
    'serialization',
    'jsonapi_extras',
    'field',
    'text',
    'options',
    'path_alias',
    'system',
    'user',
    'filter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['node', 'filter']);
    $this->installSchema('system', ['sequences']);
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('path_alias');
    $this->createContentType(['type' => 'page']);
    $config_factory = \Drupal::configFactory();
    $config_factory->getEditable('gatsby.settings')->set('preview_entity_types', [
      'node',
    ])->save();
    $this->setUpCurrentUser([], [
      'access content',
    ]);
    $this->createEntityReferenceField('node', 'page', 'field_reference', 'Referencs', 'node');
  }

  /**
   * Tests buildRelationshipJson.
   */
  public function testBuildRelationshipJson() {
    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::create([
      'type' => 'page',
      'status' => 1,
      'title' => 'foo',
    ]);
    $node->save();
    /** @var \Drupal\gatsby_instantpreview\GatsbyInstantPreview $preview */
    $preview = \Drupal::service('gatsby.gatsby_instantpreview');
    $entity_data = [];
    $data = $preview->getJson($node);
    $preview->buildRelationshipJson($data['data']['relationships'], $entity_data, ['user']);
    $this->assertTrue(!empty($entity_data[$node->uid->entity->uuid()]));

    $entity_data = [];
    $preview->buildRelationshipJson($data['data']['relationships'], $entity_data);
    $this->assertTrue(!empty($entity_data[$node->uid->entity->uuid()]));

    $node2 = Node::create([
      'type' => 'page',
      'status' => 1,
      'title' => 'foo',
      'field_reference' => $node,
    ]);
    $node2->save();

    $node->field_reference = $node2;
    $node->save();
    $entity_data = [];
    $data = $preview->getJson($node);
    $preview->buildRelationshipJson($data['data']['relationships'], $entity_data, ['node']);
    $this->assertTrue(!empty($entity_data[$node2->uuid()]));
  }

}
