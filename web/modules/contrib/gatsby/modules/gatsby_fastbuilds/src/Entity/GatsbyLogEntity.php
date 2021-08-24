<?php

namespace Drupal\gatsby_fastbuilds\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Gatsby log entity entity.
 *
 * @ingroup gatsby
 *
 * @ContentEntityType(
 *   id = "gatsby_log_entity",
 *   label = @Translation("Gatsby log entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\gatsby_fastbuilds\GatsbyLogEntityListBuilder",
 *     "views_data" = "Drupal\gatsby_fastbuilds\Entity\GatsbyLogEntityViewsData",
 *     "access" = "Drupal\gatsby_fastbuilds\GatsbyLogEntityAccessControlHandler",
 *   },
 *   base_table = "gatsby_log_entity",
 *   translatable = FALSE,
 *   admin_permission = "administer gatsby log entity entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 * )
 */
class GatsbyLogEntity extends ContentEntityBase implements GatsbyLogEntityInterface {

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['entity_uuid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity UUID'))
      ->setDescription(t('The UUID of the logged entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -3,
      ])
      ->setRequired(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the entity being logged.'))
      ->setSettings([
        'max_length' => 250,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -2,
      ])
      ->setRequired(TRUE);

    $fields['entity'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity'))
      ->setDescription(t('The entity type being logged.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -2,
      ])
      ->setRequired(TRUE);

    $fields['bundle'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Bundle'))
      ->setDescription(t('The entity bundle type being logged.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -1,
      ])
      ->setRequired(TRUE);

    $fields['action'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Action'))
      ->setDescription(
        t('The action of the entity being logged (insert, update, or delete).')
      )
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -1,
      ])
      ->setRequired(TRUE);

    $fields['json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Entity JSON'))
      ->setDescription(t('The entity JSON.'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 0,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    return $fields;
  }

}
