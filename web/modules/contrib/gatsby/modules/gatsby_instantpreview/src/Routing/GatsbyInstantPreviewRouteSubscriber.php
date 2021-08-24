<?php

namespace Drupal\gatsby_instantpreview\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class GatsbyInstantPreviewRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('gatsby.gatsby_admin_form')) {
      $route->setDefault('_form', 'Drupal\gatsby_instantpreview\Form\GatsbyInstantPreviewAdminForm');
    }
  }

}
