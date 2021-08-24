<?php

namespace Drupal\gatsby_fastbuilds\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Gatsby log entity entities.
 */
class GatsbyLogEntityViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
