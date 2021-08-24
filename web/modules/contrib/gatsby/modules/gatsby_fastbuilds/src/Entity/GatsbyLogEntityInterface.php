<?php

namespace Drupal\gatsby_fastbuilds\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining Gatsby log entity entities.
 *
 * @ingroup gatsby
 */
interface GatsbyLogEntityInterface extends ContentEntityInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the title of the logged entity.
   *
   * @return string
   *   The title of the logged entity.
   */
  public function getTitle();

  /**
   * Sets the title of the logged entity.
   *
   * @param string $title
   *   The title of the logged entity.
   *
   * @return \Drupal\gatsby\Entity\GatsbyLogEntityInterface
   *   The called Gatsby log entity entity.
   */
  public function setTitle($title);

  /**
   * Gets the Gatsby log entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Gatsby log entity.
   */
  public function getCreatedTime();

  /**
   * Sets the Gatsby log entity creation timestamp.
   *
   * @param int $timestamp
   *   The Gatsby log entity creation timestamp.
   *
   * @return \Drupal\gatsby\Entity\GatsbyLogEntityInterface
   *   The called Gatsby log entity entity.
   */
  public function setCreatedTime($timestamp);

}
