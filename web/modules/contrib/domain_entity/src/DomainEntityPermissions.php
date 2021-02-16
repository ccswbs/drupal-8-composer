<?php

namespace Drupal\domain_entity;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Dynamic permissions class for Domain Entity.
 */
class DomainEntityPermissions {

  use StringTranslationTrait;

  /**
   * Define permissions.
   */
  public function permissions() {
    $permissions = [];

    $mapper = \Drupal::service('domain_entity.mapper');
    $bundle_info = \Drupal::service('entity_type.bundle.info');

    foreach ($mapper->getEnabledEntityTypes() as $type_id => $entity_type) {
      $bundles = $bundle_info->getBundleInfo($type_id);
      if (!empty($bundles)) {
        foreach ($bundles as $bundle_id => $bundle) {
          $permissions += $this->bundlePermissions($bundle_id, $bundle['label'], $entity_type->id(), $entity_type->getBundleLabel());
        }
      }
    }

    return $permissions;
  }

  /**
   * Helper method to generate bundle permission list.
   *
   * @param int $id
   *   Bundle ID.
   * @param string $label
   *   Bundle label.
   * @param int $entityTypeId
   *   Entity type ID.
   * @param string $entityTypeLabel
   *   Entity type label.
   *
   * @see DomainAccessPermissions
   *
   * @return array
   *   An array of permission names and descriptions.
   */
  private function bundlePermissions($id, $label, $entityTypeId, $entityTypeLabel) {
    // Build standard list of bundle permissions for this type.
    $perms = [
      "create $id $entityTypeId content on assigned domains" => [
        'title' => $this->t('%entity_type_name: %type_name: Create new content on assigned domains', ['%type_name' => $label, '%entity_type_name' => $entityTypeLabel]),
      ],
      "update $id $entityTypeId content on assigned domains" => [
        'title' => $this->t('%entity_type_name: %type_name: Edit any content on assigned domains', ['%type_name' => $label, '%entity_type_name' => $entityTypeLabel]),
      ],
      "delete $id $entityTypeId content on assigned domains" => [
        'title' => $this->t('%entity_type_name: %type_name: Delete any content on assigned domains', ['%type_name' => $label, '%entity_type_name' => $entityTypeLabel]),
      ],
    ];

    return $perms;
  }

}
