<?php

namespace Drupal\build_hooks_azure;

/**
 * CircleCiManager interface.
 */
interface AzureManagerInterface {

  /**
   * Returns the build hooks details based on plugin configuration.
   *
   * @param array $config
   *   The plugin configuration array.
   *
   * @return \Drupal\build_hooks\BuildHookDetails
   *   Build hooks detail object with info about the request to make.
   */
  public function getBuildHookDetailsForPluginConfiguration(array $config);

}
