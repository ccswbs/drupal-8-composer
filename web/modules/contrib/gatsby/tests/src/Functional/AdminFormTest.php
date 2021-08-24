<?php

namespace Drupal\Tests\gatsby\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Url;

/**
 * Defines a test for GatsbyAdminForm.
 *
 * @group gatsby
 *
 * @covers \Drupal\gatsby\Form\GatsbyAdminForm
 */
class AdminFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'gatsby',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $formUrl;

  /**
   * Tests admin form validation.
   */
  public function testAdminFormValidation() {
    $this->formUrl = Url::fromRoute('gatsby.gatsby_admin_form');
    $this->assertNonAdminsCannotAccessForm();
    $this->drupalLogin($this->createUser([
      'administer gatsby',
    ]));
    $this->drupalGet($this->formUrl);
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);

    // Test an invalid server URL.
    $this->submitForm([
      'server_url' => 'this is not a URL!',
    ], 'Save configuration');

    $assert->pageTextContains('Invalid Gatsby preview server URL.');

    // Test an invalid server URL, multi-value.
    $this->submitForm([
      'server_url' => 'https://example.com, this is not a URL!',
    ], 'Save configuration');

    $assert->pageTextContains('Invalid Gatsby preview server URL.');

    // Test an invalid preview URL.
    $this->submitForm([
      'preview_callback_url' => 'this is not a URL!',
    ], 'Save configuration');

    $assert->pageTextContains('Invalid Gatsby preview callback URL.');

    // Test an invalid preview URL, multi-value.
    $this->submitForm([
      'preview_callback_url' => 'https://example.com, this is not a URL!',
    ], 'Save configuration');

    $assert->pageTextContains('Invalid Gatsby preview callback URL.');

    // Test an invalid incrementalbuild_url.
    $this->submitForm([
      'incrementalbuild_url' => 'this is not a URL!',
    ], 'Save configuration');

    $assert->pageTextContains('Invalid incremental build URL.');

    // Test an invalid path-mapping.
    $this->submitForm([
      'path_mapping' => <<<MAPPING
invalid|/mapping
MAPPING,
    ], 'Save configuration');

    $assert->pageTextContains('Invalid preview server path mapping.');

    // Submit valid values.
    $server_url = sprintf('https://%s.com', $this->randomMachineName());
    $build_url = sprintf('https://%s.com', $this->randomMachineName());
    $mapping = <<<MAPPING
/home|/
MAPPING;
    $this->submitForm([
      'server_url' => $server_url,
      'preview_callback_url' => $server_url . '/__refresh',
      'incrementalbuild_url' => $build_url,
      'path_mapping' => $mapping,
      'build_published' => TRUE,
      'preview_entity_types[user]' => 1,
    ], 'Save configuration');

    // Assert the new values are saved.
    $config = \Drupal::config('gatsby.settings');
    $this->assertEquals($server_url, $config->get('server_url'));
    $this->assertEquals($server_url . '/__refresh', $config->get('preview_callback_url'));
    $this->assertEquals($build_url, $config->get('incrementalbuild_url'));
    $this->assertEquals($mapping, $config->get('path_mapping'));
    $this->assertTrue($config->get('build_published'));
    $this->assertEquals(['user'], $config->get('preview_entity_types'));
  }

  /**
   * Assert non admins can't access the form.
   */
  private function assertNonAdminsCannotAccessForm() {
    $this->drupalGet($this->formUrl);
    $this->assertSession()->statusCodeEquals(403);
  }

}
