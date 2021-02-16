<?php

namespace Drupal\build_hooks_azure;

use Drupal\build_hooks\BuildHookDetails;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;
use DateTimeZone;
use DateTime;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Yaml\Yaml;

/**
 * Class AzureManager.
 */
class AzureManager implements AzureManagerInterface {

  use StringTranslationTrait;
  use MessengerTrait;

  const URI_API = '_apis';

  const URI_DEF = [
    'build' => 'build/definitions',
    'release' => 'release/definitions',
  ];

  const URI_TRIGGER = [
    'build' => 'build/builds',
    'release' => 'release/releases',
  ];

  /**
   * Internal path for the mapping.
   */
  const PATH_MAPPING = '/src/Mapping';

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The Date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Module handler instance.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;


  /**
   * AzureManager constructor.
   *
   * {@inheritDoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client, DateFormatterInterface $date_formatter, ModuleHandlerInterface $module_handler) {
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
    $this->dateFormatter = $date_formatter;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Get the http client instance.
   *
   * @return \GuzzleHttp\ClientInterface
   *   The http client.
   */
  public function getHttpClient() {
    return $this->httpClient;
  }

  /**
   * Returns the build hooks details based on plugin configuration.
   *
   * @param array $config
   *   The plugin configuration array.
   *
   * @return \Drupal\build_hooks\BuildHookDetails
   *   Build hooks detail object with info about the request to make.
   *
   * @throws \Exception
   */
  public function getBuildHookDetailsForPluginConfiguration(array $config) {
    $buildHookDetails = new BuildHookDetails();
    $build_url = $this->buildAzureUrlForEnvironment($config);
    if (!$build_url) {
      throw new \Exception($this->t('Azure front environment is not well configure.'));
    }

    $buildHookDetails->setUrl($build_url);
    $buildHookDetails->setMethod('POST');
    $option = $this->getAuth($config);

    if($config['trigger_type'] == 'build'){
	    $option['json'] = ['definition' => ['id' => $config['trigger_id']]];
    }else{
    	$option['json'] = ['definitionId' => $config['trigger_id']];
    }
    // Set header token.
    $buildHookDetails->setBody($option);

    return $buildHookDetails;
  }

  /**
   * Format the basic auth with the username and personal token as password.
   *
   * @param array $config
   *   The config of plugin.
   *
   * @return array
   *   The array for basic auth for http client.
   */
  public function getAuth(array $config) {
    if (empty($config['username']) || empty($config['token_access'])) {
      return FALSE;
    }
    return [
      'auth' => [
        $config['username'],
        $config['token_access'],
      ],
    ];
  }

  /**
   * Build the url to trigger azure build depending on the environment.
   *
   * @param array $config
   *   The plugin config.
   * @param bool $definition
   *   If true get the definition of trigger.
   * @param array $uri_addional
   *   Add some uri parameter (like id)
   * @param array $url_parameter
   *   Add url parameter.
   *
   * @return string
   *   The url of Azure api.
   *
   * @throws \Exception
   */
  public function buildAzureUrlForEnvironment(array $config, $definition = FALSE, array $uri_addional = [], array $url_parameter = []) {

    $settings = $this->configFactory->get('build_hooks_azure.settings');
    $organization = $settings->get('organization');
    $project = $settings->get('project');
    if (empty($config['trigger_type']) || empty($organization) || empty($project)) {
      return FALSE;
    }
    $trigger_type = $config['trigger_type'];
    $uri = self::URI_API;
    if ($definition == FALSE) {
      $uri .= '/' . self::URI_TRIGGER[$trigger_type];
    }
    else {
      $uri .= '/' . self::URI_DEF[$trigger_type];
    }

    if (!empty($uri_addional)) {
      $uri .= '/' . implode('/', $uri_addional);
    }
    $url_parameter['api-version'] = $config['api_version'];
    $url_parameter = UrlHelper::buildQuery($url_parameter);
    return $config['build_hook_url'] . '/' . $organization . '/' . $project . '/' . $uri . '?' . $url_parameter;
  }

  /**
   * Get the list of trigger definition (IE : build or realease definition).
   *
   * @param array $config
   *   The plugin configuration.
   *
   * @return array|string
   *   Table of trigger definition.
   *
   * @throws \Exception
   */
  public function getTriggerDefinitions(array $config) {
    try {
      $url = $this->buildAzureUrlForEnvironment($config, TRUE);
      if ($url == FALSE) {
        return ['#markup' => '<div id="ajax-trigger-definition"></div>'];
      }
      $response = $this->getHttpClient()
        ->request('GET', $url, $this->getAuth($config));
      $reponse = json_decode($response->getBody()->getContents(), TRUE);

    }
    catch (GuzzleException $e) {
      $error = [
        'Failed to get build definition on Azure. Error message: <pre> @message </pre>',
        ['@message' => $e->getMessage()],
      ];
      $this->messenger()
        ->addError($this->t('Failed to get build definition on Azure. Error message: <pre> @message </pre>', $error[1]));
    }
    if (empty($reponse['value'])) {
      return ['#markup' => '<div id="ajax-trigger-definition">' . $this->t('No build found') . '</div>'];
    }
    $header = [
      'id' => $this->t('Id'),
      'name' => $this->t('Name'),
      'created' => $this->t('Created'),
      'author' => $this->t('Author'),
    ];
    $element = [
      '#type' => 'table',
      '#attributes' => ['id' => 'ajax-trigger-definition'],
      '#tableselect' => TRUE,
      '#header' => $header,
    ];
    if (empty($config['timezone'])) {
      return ['#markup' => '<div id="ajax-trigger-definition"></div>'];
    }

    $mapping = $this->getMapping($config['trigger_type']);
    if (empty($mapping['fields'])) {
      return FALSE;
    }
    foreach ($reponse['value'] as $trigger) {
      $id = $trigger[$mapping['fields']['id']];

      $element[$id]['id'] = [
        '#type' => 'item',
        '#markup' => '<strong>' . $id . '</strong>',
      ];
      $element[$id]['Name'] = [
        '#type' => 'item',
        '#markup' => $trigger[$mapping['fields']['name']],
      ];
      $element[$id]['created'] = [
        '#type' => 'item',
        '#markup' => $this->formatAzureDateTime($trigger[$mapping['fields']['created']], $config['timezone']),
      ];
      $element[$id]['author'] = [
        '#type' => 'item',
        '#markup' => $trigger[$mapping['fields']['author']]['displayName'],
      ];

    }
    return $element;
  }

  /**
   * @param $sub_form_state
   *
   * @return array
   */
  public function getFormStateValues($sub_form_state, $include_input = TRUE) {
    $form_state = $sub_form_state;
    if ($sub_form_state instanceof SubformStateInterface) {
      $form_state = $sub_form_state->getCompleteFormState();
    }
    $values = $form_state->getValues();
    $inputs = $form_state->getUserInput();
    // Rebuilding tree bug.
    if (!empty($values['settings']['ajax_trigger'])) {
      $values['settings'] = array_merge($values['settings'], $values['settings']['ajax_trigger']);
    }
    if (!$include_input || !isset($inputs['settings'])) {
      return $values;
    }
    foreach ($inputs['settings'] as $key => $setting) {
      if (!isset($values['settings'][$key])) {
        $values['settings'][$key] = $setting;
      }
      elseif (is_array($values['settings'][$key])) {
        $values['settings'][$key] = array_merge($values['settings'][$key], $setting);
      }

    }

    return $values;
  }

  /**
   * Extract the azure field name of mapping.
   *
   * @param array $data
   * @param $mapping
   * @param $key
   * @param string $type
   *
   * @return bool|mixed
   */
  public function extractDataWithMapping(array $data, $mapping, $key, $type = 'fields') {
    if ($data[$mapping[$type][$key]]) {
      if (!is_array($data[$mapping[$type][$key]])) {
        return $data[$mapping[$type][$key]];
      }
      else {
        return array_reduce(array_keys($mapping[$type][$key]), 'keyReduce', $data[$mapping[$type][$key]]);
      }
    }
    return FALSE;
  }

  /**
   * @param array $arr
   * @param $idx
   *
   * @return mixed|null
   */
  private function keyReduce(array $arr, $idx) {
    return array_key_exists($idx, $arr) ? $arr[$idx] : NULL;
  }

  /**
   * Get the latest builds from Azure for and environment.
   *
   * @param array $settings
   *   The plugin settings array.
   * @param $min_date
   * @param $max_date
   *
   * @return array
   *   An array with info about the builds.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function retrieveLatestBuildsFromAzureForEnvironment(array $settings, $min_date, $max_date) {

    $mapping = $this->getMapping($settings['trigger_type']);
    if (empty($mapping['url_parameter'])) {
      return FALSE;
    }
    // @todo verify this doesnt change with api version.....
    $url_parameter = [
      $mapping['url_parameter']['definition'] => $settings['trigger_id'],
      $mapping['url_parameter']['min_date'] => $min_date,
      $mapping['url_parameter']['max_date'] => $max_date,
    ];
    // Get the list of build/release.
    $url = $this->buildAzureUrlForEnvironment($settings, FALSE, [], $url_parameter);

    if ($url != FALSE) {
      $response = $this->httpClient->request('GET', $url, $this->getAuth($settings));
      $payload = json_decode($response->getBody()->getContents(), TRUE);
      return $payload;
    }

  }

  /**
   * Converts the datetime format into a drupal formatted date.
   *
   * @param string $datetime
   *   The date time to convert.
   * @param string $timezone
   *   The timezone (should be always UTC)
   * @param string $type
   *   The format of drupal date.
   *
   * @return bool|string
   *   False if error, else the drupal formatted date.
   *
   * @throws \Exception
   */
  public function formatAzureDateTime($datetime, $timezone, $type = 'long') {
    // Dates are in UTC format:
    $timezone = new DateTimeZone($timezone);
    // @dev do not use here DateTime createFromFormat with ISO8601.
    // @see https://bugs.php.net/bug.php?id=51950.
    $date = new DateTime($datetime, $timezone);
    if (!$date) {
      return FALSE;
    }
    return $this->dateFormatter->format($date->getTimestamp(), $type);
  }

  /**
   * Return the mapping to the REST azure API.
   *
   * @param string $trigger_type
   *   Trigger type of azure.
   *
   * @return bool|array
   *   The content of mapping, or FALSE if there are no mapping.
   */
  public function getMapping($trigger_type) {
    $filename = $trigger_type . '.yml';
    $module_path = $this->moduleHandler->getModule('build_hooks_azure')->getPath() . self::PATH_MAPPING;
    $files = file_scan_directory($module_path, '/^.*\.yml$/i', ['recurse' => 1]);

    // Search file with view mode.
    foreach ($files as $file) {
      if ($file->filename == $filename) {
        return Yaml::parse(file_get_contents($file->uri));
      }
    }
    return FALSE;
  }

}
