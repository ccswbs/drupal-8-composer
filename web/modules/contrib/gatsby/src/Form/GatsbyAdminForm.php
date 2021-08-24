<?php

namespace Drupal\gatsby\Form;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\gatsby\PathMapping;

/**
 * Defines a config form to store Gatsby configuration.
 */
class GatsbyAdminForm extends ConfigFormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Extension\ModuleHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Drupal\gatsby\PathMapping definition.
   *
   * @var \Drupal\gatsby\PathMapping
   */
  protected $pathMapping;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\gatsby\PathMapping $pathMapping
   *   The path mapping.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ModuleHandlerInterface $moduleHandler, PathMapping $pathMapping) {
    $this->entityTypeManager = $entityTypeManager;
    $this->moduleHandler = $moduleHandler;
    $this->pathMapping = $pathMapping;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('gatsby.path_mapping')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'gatsby.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gatsby_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('gatsby.settings');
    $form['server_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Gatsby Preview Server URL'),
      '#description' => $this->t('The URL to the Gatsby preview server (with port number if needed). Separate multiple values with a comma.'),
      '#default_value' => $config->get('server_url'),
      '#required' => TRUE,
      '#maxlength' => 250,
      '#weight' => 0,
    ];
    $form['preview_callback_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Gatsby Preview Callback URL'),
      '#description' => $this->t('The URL to the Gatsby preview build webhook (if running locally, it\'s "localhost:8000/__refresh"). Separate multiple values with a comma.'),
      '#default_value' => $config->get('preview_callback_url'),
      '#required' => TRUE,
      '#maxlength' => 250,
      '#weight' => 0,
    ];
    $form['path_mapping'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Preview Server Path Mapping'),
      '#description' => $this->t('If you do any path manipulation in your Gatsby
        site you may need to map the preview iframe and preview button to this
        correct path. For instance, you may have a /home path in Drupal that
        maps to / on your Gatsby site. Enter the Drupal path on the left
        (starting with a "/") and the Gatsby path on the right (starting with
        a "/") separated by a "|" character. For example: "/home|/". Enter one
        path mapping per line.'),
      '#default_value' => $config->get('path_mapping'),
    ];

    $build_title = $this->t("Build Server Callback Hook(s)");
    $build_description = $this->t('The Callback URL to trigger the Gatsby Build. Multiple build server URLS can be separated by commas. Note: Incremental builds are currently only supported with JSON:API and gatsby-source-drupal.');
    if ($this->moduleHandler->moduleExists('jsonapi_extras')) {
      $build_title = $this->t("Incremental Build Server Callback Hook(s)");
      $build_description = $this->t('The Callback URL to the Gatsby incremental builds server. Multiple build server URLS can be separated by commas.');
    }
    $form['incrementalbuild_url'] = [
      '#type' => 'textfield',
      '#title' => $build_title,
      '#description' => $build_description,
      '#default_value' => $config->get('incrementalbuild_url'),
      '#maxlength' => 250,
      '#weight' => 1,
    ];
    $form['build_published'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Only trigger builds for published content'),
      '#description' => $this->t('Depending on your content workflow, you may only
        want builds to be triggered for published content. By checking this box
        only published content will trigger a build. This means additional entities
        such as Media or Files will not trigger a rebuild until the content it\'s
        attached to is published. The downside is that this will
        only allow content entities to trigger a rebuild.'),
      '#default_value' => $config->get('build_published') !== NULL ? $config->get('build_published') : TRUE,
      '#weight' => 2,
    ];
    $form['preview_entity_types'] = [
      '#type' => 'checkboxes',
      '#options' => $this->getContentEntityTypes(),
      '#default_value' => $config->get('preview_entity_types') ?: [],
      '#title' => $this->t('Entity types to send to Gatsby Preview and Build Server'),
      '#description' => $this->t('What entities should be sent to the Gatsby Preview and Build Server?'),
      '#weight' => 10,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $this->validateCsvUrl('server_url', $this->t('Invalid Gatsby preview server URL.'), $form_state);
    $this->validateCsvUrl('preview_callback_url', $this->t('Invalid Gatsby preview callback URL.'), $form_state);

    $path_mapping = $form_state->getValue('path_mapping');
    try {
      $map = PathMapping::parsePathMapping($path_mapping);
      if (strlen(trim($path_mapping)) > 0 && count($map) === 0) {
        // Unable to parse anything meaningful from the path mapping.
        $form_state->setErrorByName('path_mapping', $this->t('Invalid preview server path mapping.'));
      }
    }
    catch (\Error $e) {
      // Parsing the path mapping caused a PHP Error.
      $form_state->setErrorByName('path_mapping', $this->t('Invalid preview server path mapping.'));
    }

    $incremental_build_url = $form_state->getValue('incrementalbuild_url');
    if (strlen($incremental_build_url) > 0 and !filter_var($incremental_build_url, FILTER_VALIDATE_URL)) {
      $form_state->setErrorByName('incrementalbuild_url', $this->t('Invalid incremental build URL.'));
    }
  }

  /**
   * Validates a URL that may be multi-value via commas.
   *
   * @param string $field_name
   *   Field name.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $error
   *   Error message to show if invalid.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  protected function validateCsvUrl(string $field_name, TranslatableMarkup $error, FormStateInterface $form_state) : void {
    $urls = array_map('trim', explode(',', $form_state->getValue($field_name)));
    foreach ($urls as $url) {
      if (strlen($url) > 0 and !filter_var($url, FILTER_VALIDATE_URL)) {
        $form_state->setErrorByName($field_name, $error);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('gatsby.settings')
      ->set('server_url', $form_state->getValue('server_url'))
      ->set('preview_callback_url', $form_state->getValue('preview_callback_url'))
      ->set('incrementalbuild_url', $form_state->getValue('incrementalbuild_url'))
      ->set('build_published', $form_state->getValue('build_published'))
      ->set('preview_entity_types', array_values(array_filter($form_state->getValue('preview_entity_types'))))
      ->set('path_mapping', $form_state->getValue('path_mapping'))
      ->save();
  }

  /**
   * Gets a list of all the defined content entities in the system.
   *
   * @return array
   *   An array of content entities definitions.
   */
  private function getContentEntityTypes() {
    $content_entity_types = [];
    $allEntityTypes = $this->entityTypeManager->getDefinitions();

    foreach ($allEntityTypes as $entity_type_id => $entity_type) {
      // Add all content entity types but not the gatsby log entity provided
      // by the gatsby_fastbuilds module (if it exists).
      if ($entity_type instanceof ContentEntityTypeInterface &&
        $entity_type_id !== 'gatsby_log_entity') {

        $content_entity_types[$entity_type_id] = $entity_type->getLabel();
      }
    }
    return $content_entity_types;
  }

}
