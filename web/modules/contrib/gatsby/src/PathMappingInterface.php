<?php

namespace Drupal\gatsby;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an interface for mapping paths between Drupal and Gatsby.
 */
interface PathMappingInterface {

  /**
   * Gets the Gatsby path for a content-entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to get the Gatsby path for.
   *
   * @return string
   *   The Gatsby path for the entity.
   */
  public function getPath(EntityInterface $entity) : string;

}
