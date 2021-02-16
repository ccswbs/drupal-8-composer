<?php

namespace Drupal\build_hooks_azure\Plugin\FrontendEnvironment;

use Drupal\build_hooks\Plugin\FrontendEnvironmentBase;
use Drupal\build_hooks_azure\AzureManager;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Exception;
use DateTime;

/**
 * Provides a 'CircleCI' frontend environment type.
 *
 * @FrontendEnvironment(
 *  id = "azure",
 *  label = "Azure",
 *  description = "An environment connected to Azure"
 * )
 */
class AzureFrontendEnvironment extends FrontendEnvironmentBase implements ContainerFactoryPluginInterface {

  use MessengerTrait;

  const DATE_FORMAT = DateTime::ISO8601;

  /**
   * The azure manager service.
   *
   * @var \Drupal\build_hooks_azure\AzureManager
   */
  protected $azureManger;

  /**
   * AzureFrontendEnvironment constructor.
   *
   * {@inheritDoc}.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AzureManager $azure_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->azureManger = $azure_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('build_hooks_azure.azure_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function frontEndEnvironmentForm($form, FormStateInterface $form_state) {
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#maxlength' => 255,
      '#default_value' => isset($this->configuration['username']) ? $this->configuration['username'] : '',
      '#description' => $this->t("The username of azure"),
      '#required' => TRUE,
    ];

    $form['token_access'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token access'),
      '#maxlength' => 255,
      '#default_value' => isset($this->configuration['token_access']) ? $this->configuration['token_access'] : '',
      '#description' => $this->t("The personal access token."),
      '#required' => TRUE,
    ];
    $form['api_version'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Api version'),
      '#maxlength' => 10,
      '#default_value' => isset($this->configuration['api_version']) ? $this->configuration['api_version'] : '',
      '#description' => $this->t("The api version."),
      '#required' => TRUE,
    ];
    $form['timezone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Azure timezone'),
      '#maxlength' => 5,
      '#default_value' => isset($this->configuration['timezone']) ? $this->configuration['timezone'] : 'UTC',
      '#description' => $this->t("Azure timzeone."),
      '#required' => TRUE,
    ];
    $form['ajax_trigger'] = [
      '#type' => 'container',
      '#prefix' => '<div id="ajax-trigger">',
      '#suffix' => '</div>',
    ];
    $trigger_type_default = $this->getDefaultValue($form_state, $this->configuration, 'trigger_type');
    if (empty($trigger_type_default)) {
      $trigger_type_default = 'release';
    }
    $option_trigger = ['release' => t('Release'), 'build' => t('Build')];
    $form['ajax_trigger']['trigger_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Trigger type'),
      '#options' => $option_trigger,
      '#ajax' => [
        'callback' => [AzureFrontendEnvironment::class, 'ajaxTrigger'],
        'wrapper' => 'ajax-trigger',
        'method' => 'replaceWith',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
      '#default_value' => $trigger_type_default,
      '#description' => $this->t("Azure trigger type."),
      '#required' => TRUE,
    ];
    $form['ajax_trigger']['build_hook_url'] = [
      '#type' => 'url',
      '#title' => $this->t(':trigger_type build hook url', [':trigger_type' => $option_trigger[$trigger_type_default]]),
      '#maxlength' => 255,
      '#default_value' => isset($this->configuration['build_hook_url']) ? $this->configuration['build_hook_url'] : '',
      '#description' => $this->t(":trigger_type build hook url for this environment.", [':trigger_type' => $option_trigger[$trigger_type_default]]),
      '#required' => TRUE,
    ];
    $suffix = '<div id="ajax-trigger-definition"></div>';
    $form_state_values = $this->azureManger->getFormStateValues($form_state);
    if (empty($form_state_values['settings'])) {
      $form_state_values['settings'] = $this->configuration;
    }

    // Get definitions.
    if (!empty($form_state_values['settings'])) {
      $elements = $this->azureManger->getTriggerDefinitions($form_state_values['settings']);
      if (!empty($elements)) {
        $suffix = render($elements);
      }
    }
    $form['ajax_trigger']['trigger_id'] = [
      '#type' => 'number',
      '#title' => $this->t(':trigger_type id', [':trigger_type' => $option_trigger[$trigger_type_default]]),
      '#min' => 0,
      '#maxlength' => 255,
      '#default_value' => isset($this->configuration['trigger_id']) ? $this->configuration['trigger_id'] : '',
      '#description' => $this->t("The :trigger_type id you want to start.", [':trigger_type' => $option_trigger[$trigger_type_default]]),
      '#required' => TRUE,
      '#suffix' => $suffix,
    ];
    $form['ajax_trigger']['trigger_definition'] = [
      '#type' => 'button',
      '#value' => $this->t('Get :trigger_type definitions', [':trigger_type' => $option_trigger[$trigger_type_default]]),
      '#ajax' => [
        'callback' => [
          get_class($this),
          'ajaxTriggerDefinitions',
        ],
        'wrapper' => 'ajax-trigger-definition',
        'method' => 'replaceWith',
      ],
      '#limit_validation_errors' => [],
    ];
    return $form;
  }

  /**
   * Return the part of ajax trigger.
   *
   * @todo change suggest url.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function ajaxTrigger($form, FormStateInterface $form_state) {
    return $form['ajax_trigger'];
  }

  /**
   * Get the definitions of trigger (release or build).
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The data of form.
   *
   * @return array
   *   Form element to replace.
   *
   * @throws \Exception
   */
  public function ajaxTriggerDefinitions(array $form, FormStateInterface $form_state) {

    if (!isset($this)) {
      $azure_manager = \Drupal::service('build_hooks_azure.azure_manager');
    }
    else {
      $azure_manager = $this->azureManger;
    }
    $config = $azure_manager->getFormStateValues($form_state, FALSE);
    if (!empty($config['settings'])) {
      $config = $config['settings'];
    }
    if (empty($config['timezone'])) {
      $form_state->setErrorByName('timezone', t('You must to set timezone before get build definitions'));
    }

    return $azure_manager->getTriggerDefinitions($config);

  }

  /**
   * {@inheritdoc}
   */
  public function frontEndEnvironmentSubmit($form, FormStateInterface $form_state) {
    $this->configuration['username'] = $form_state->getValue('username');
    $this->configuration['token_access'] = $form_state->getValue('token_access');
    $this->configuration['api_version'] = $form_state->getValue('api_version');
    $this->configuration['timezone'] = $form_state->getValue('timezone');

    $ajax_trigger = $form_state->getValue('ajax_trigger');
    $this->configuration['build_hook_url'] = $ajax_trigger['build_hook_url'] ?? $ajax_trigger['build_hook_url'];
    $this->configuration['trigger_id'] = $ajax_trigger['trigger_id'] ?? $ajax_trigger['trigger_id'];
    $this->configuration['trigger_type'] = $ajax_trigger['trigger_type'] ?? $ajax_trigger['trigger_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getBuildHookDetails() {

    try {
      return $this->azureManger->getBuildHookDetailsForPluginConfiguration($this->getConfiguration());
    }
    catch (Exception $e) {
      $this->messenger()->addError($e->getMessage());
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getAdditionalDeployFormElements(FormStateInterface $form_state) {
    $config = $this->getConfiguration();
    // This plugin adds to the deployment form a fieldset displaying the
    // latest deployments:
    $form = [];
    $min_date = $form_state->getValue('min_date');
    if (!empty($min_date) && $min_date instanceof DrupalDateTime) {
      $min_date = $min_date->format(DateTime::ISO8601);
    }
    else {
      $min_date = new DrupalDateTime('-1 day');
    }
    $max_date = $form_state->getValue('max_date');
    if (!empty($max_date) && $max_date instanceof DrupalDateTime) {
      $max_date = $max_date->format(DateTime::ISO8601);
    }
    else {
      $max_date = new DrupalDateTime();
    }
    $form['min_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Date minimum'),
      '#description' => $this->t('Filters to :trigger_type that finished/started/queued before this date based on the queryOrder specified.', [':trigger_type' => $config['trigger_type']]),
      '#date_format' => self::DATE_FORMAT,
      '#default_value' => $min_date,
      '#date_date_format' => 'Y-m-d',
      '#date_time_format' => 'H:i:s',
      '#date_timezone' => $config['timezone'],
      '#required' => TRUE,
    ];
    $form['max_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Date maximum'),
      '#description' => $this->t('Filters to :trigger_type that finished/started/queued before this date based on the queryOrder specified.', [':trigger_type' => $config['trigger_type']]),
      '#date_format' => self::DATE_FORMAT,
      '#default_value' => $max_date,
      '#date_date_format' => 'Y-m-d',
      '#date_time_format' => 'H:i:s',
      '#date_timezone' => $config['timezone'],
    ];
    $form['last_azure_deployments'] = [
      '#type' => 'details',
      '#title' => $this->t('Recent deployments'),
      '#description' => $this->t('Here you can see the details for the latest deployments for this environment.'),
      '#open' => TRUE,
    ];

    $form['last_azure_deployments']['table'] = $this->getLastAzureDeploymentsTable($this->getConfiguration(), $min_date->format(self::DATE_FORMAT), $max_date->format(self::DATE_FORMAT));
    $form['last_azure_deployments']['refresher'] = [
      '#type' => 'button',
      '#ajax' => [
        'callback' => [
          AzureFrontendEnvironment::class,
          'refreshDeploymentTable',
        ],
        'wrapper' => 'ajax-replace-table',
        'effect' => 'fade',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Refreshing deployment status...'),
        ],
      ],
      '#value' => $this->t('Refresh'),
    ];
    return $form;
  }

  /**
   * Gets info about the latest circle ci deployments for this environment.
   *
   * @param array $settings
   *   The plugin settings array.
   * @param string $min_date
   *   The minimun date to fetch.
   * @param string $max_date
   *   The maximum date to fetch.
   *
   * @return array
   *   Render array.
   *
   * @throws \Exception
   */
  private function getLastAzureDeploymentsTable(array $settings, $min_date, $max_date) {

    try {
      $azure_logs = $this->azureManger->retrieveLatestBuildsFromAzureForEnvironment($settings, $min_date, $max_date);
      $element = [
        '#type' => 'table',
        '#title' => $this->t('Logs'),
        '#description' => $this->t(':count Logs', [':count' => $azure_logs['count']]),
        '#attributes' => ['id' => 'ajax-replace-table'],
        '#header' => [
          $this->t('Id'),
          $this->t('Name'),
          $this->t('Status'),
          $this->t('Created'),
          $this->t('Author'),
          $this->t('Reason'),
          $this->t('Links'),
        ],
      ];
      if (!isset($azure_logs['value'])) {
        return $element;
      }
      $mapping = $this->azureManger->getMapping($settings['trigger_type']);
      if (empty($mapping['fields'])) {
        return $element;
      }
      foreach ($azure_logs['value'] as $azure_log) {


        $id = $azure_log[$mapping['fields']['id']];

        $element[$id]['id'] = [
          '#type' => 'item',
          '#markup' => '<strong>' . $id . '</strong>',
        ];

        $element[$azure_log['id']]['name'] = [
          '#type' => 'item',
          '#markup' => $azure_log[$mapping['fields']['name']],
        ];

        $element[$azure_log['id']]['status'] = [
          '#type' => 'item',
          '#markup' => $azure_log[$mapping['fields']['status']],
        ];
        $element[$azure_log['id']]['created'] = [
          '#type' => 'item',
          '#markup' => $this->azureManger->formatAzureDateTime($azure_log[$mapping['fields']['created']], $settings['timezone']),
        ];

        $element[$azure_log['id']]['author'] = [
          '#type' => 'item',
          '#markup' => $azure_log[$mapping['fields']['author']]['displayName'],
        ];
        $element[$azure_log['id']]['reason'] = [
          '#type' => 'item',
          '#markup' => $azure_log[$mapping['fields']['reason']],
        ];

        $links = [];

        $url_value = $this->azureManger->extractDataWithMapping($azure_log, $mapping, 'url', 'fields');
        if (!empty($url_value)) {
          $url = Url::fromUri($url_value);
          $links[] = Link::fromTextAndUrl(t('View'), $url)->toString();
        }
        $url_value = $this->azureManger->extractDataWithMapping($azure_log, $mapping, 'logs_url', 'fields');
        if (!empty($url_value)) {
          $url = Url::fromUri($url_value);
          $links[] = Link::fromTextAndUrl(t('Logs'), $url)->toString();
        }

        if (!empty($azure_log["_links"]["web"]["href"])) {
          $url = Url::fromUri($azure_log["_links"]["web"]["href"]);
          $links[] = Link::fromTextAndUrl(t('Summary'), $url)->toString();
        }
        $element[$azure_log['id']]['links'] = [
          '#type' => 'item',
          '#markup' => implode(',', $links),
        ];
      }


      return $element;
    }
    catch (GuzzleException $e) {
      $this->messenger()
        ->addError($e->getMessage());
    }
  }

  /**
   * Ajax form callback to rebuild the latest deployments table.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the form.
   *
   * @return array
   *   The form array to add back to the form.
   */
  public function refreshDeploymentTable(array $form, FormStateInterface $form_state) {
    return $form['last_azure_deployments']['table'];
  }

  /**
   * Return default value for element form.
   *
   * @param $sub_form_state
   *   The current state of the form.
   * @param array $config
   * @param $key_value
   *   The field key searched.
   *
   * @return mixed|string
   *   The value of field.
   */
  private function getDefaultValue($sub_form_state, array $config, $key_value) {
    $form_state = $sub_form_state;
    if ($sub_form_state instanceof SubformStateInterface) {
      $form_state = $sub_form_state->getCompleteFormState();
    }
    $value = $form_state->getValue('ajax_trigger');

    if (!empty($value[$key_value])) {
      return $value[$key_value];
    }
    return !empty($config[$key_value]) ? $config[$key_value] : '';
  }

}
