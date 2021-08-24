<?php

namespace Drupal\gatsby_fastbuilds;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Gatsby log entity entity.
 *
 * @see \Drupal\gatsby\Entity\GatsbyLogEntity.
 */
class GatsbyLogEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\gatsby\Entity\GatsbyLogEntityInterface $entity */

    switch ($operation) {

      case 'view':

        return AccessResult::allowedIfHasPermission($account, 'view gatsby log entity entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit gatsby log entity entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete gatsby log entity entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add gatsby log entity entities');
  }

}
