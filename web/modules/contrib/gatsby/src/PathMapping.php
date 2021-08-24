<?php

namespace Drupal\gatsby;

use Drupal\Core\Entity\EntityInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Defines a class for mapping paths between Drupal and Gatsby.
 */
class PathMapping implements PathMappingInterface {

  /**
   * The alias manager service.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $pathAliasManager;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new PathMapping object.
   *
   * @param \Drupal\path_alias\AliasManagerInterface $pathAliasManager
   *   Path alias manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   */
  public function __construct(AliasManagerInterface $pathAliasManager, ConfigFactoryInterface $configFactory) {
    $this->pathAliasManager = $pathAliasManager;
    $this->configFactory = $configFactory;
  }

  /**
   * Function to parse path mapping.
   *
   * @param string $path_mapping
   *   Path mapping as a string. Each line is a mapping in format like so:
   *   {drupal_path}|{gatsby_path} e.g. /home|/.
   *
   * @return array
   *   Array of Gatsby paths keyed by Drupal path.
   */
  public static function parsePathMapping(string $path_mapping) {
    $map = [];
    if (empty($path_mapping)) {
      return $map;
    }

    $paths = explode(PHP_EOL, $path_mapping);
    foreach ($paths as $path) {
      if ($split = stripos($path, '|')) {
        $drupal_path = substr($path, 0, $split);
        $gatsby_path = substr($path, $split + 1);
        if (empty($drupal_path) || empty($gatsby_path)) {
          return $map;
        }
        if (!str_starts_with($drupal_path, '/') || !str_starts_with($gatsby_path, '/')) {
          return $map;
        }

        $map[$drupal_path] = $gatsby_path;
      }
    }
    return $map;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath(EntityInterface $entity) : string {
    $alias = $this->pathAliasManager->getAliasByPath('/node/' . $entity->id());

    // If this is the front-page we don't want to pass the alias
    // (as Gatsby will likely 404).
    if ($alias === \Drupal::config('system.site')->get('page.front')) {
      $alias = '';
    }

    // Check if there are any path mappings to be considered for this alias.
    $path_mapping = $this->getPathMapping();

    if (!empty($path_mapping[$alias])) {
      $alias = $path_mapping[$alias];
    }

    return $alias;
  }

  /**
   * {@inheritdoc}
   */
  protected function getPathMapping() {
    $settings = $this->configFactory->get('gatsby.settings');
    return self::parsePathMapping($settings->get('path_mapping'));
  }

}
