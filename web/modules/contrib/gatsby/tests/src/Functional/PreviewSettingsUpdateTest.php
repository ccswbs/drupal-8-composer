<?php

namespace Drupal\Tests\gatsby\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Defines a class for testing the update path.
 *
 * @group gatsby
 */
class PreviewSettingsUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../update/fixtures/minimal-9-2-update-3160476.php.gz',
    ];
  }

  /**
   * Tests update path.
   */
  public function testUpdatePath() {
    $gatsby_settings = \Drupal::config('gatsby.settings');
    $preview_settings = $gatsby_settings->get('preview');
    $target_settings = $gatsby_settings->get('target');
    $iframe_settings = $gatsby_settings->get('iframe');
    $this->assertEquals(['article' => 1, 'page' => 1], $preview_settings);
    $this->assertEquals(['article' => 1, 'page' => 0], $iframe_settings);
    $this->assertEquals([
      'article' => 'window',
      'page' => 'sidebar',
    ], $target_settings);
    $this->assertEquals('https://example.com', $gatsby_settings->get('server_url'));
    $this->assertEmpty($gatsby_settings->get('preview_callback_url'));

    $this->runUpdates();

    $gatsby_settings = \Drupal::config('gatsby.settings');
    $this->assertEmpty($gatsby_settings->get('preview'));
    $this->assertEmpty($gatsby_settings->get('target'));
    $this->assertEmpty($gatsby_settings->get('iframe'));

    /** @var \Drupal\node\NodeTypeInterface $page */
    $page = NodeType::load('page');
    $this->assertTrue($page->getThirdPartySetting('gatsby', 'preview'));
    $this->assertEquals('sidebar', $page->getThirdPartySetting('gatsby', 'target'));
    $this->assertFalse($page->getThirdPartySetting('gatsby', 'iframe'));

    /** @var \Drupal\node\NodeTypeInterface $article */
    $article = NodeType::load('article');
    $this->assertTrue($article->getThirdPartySetting('gatsby', 'preview'));
    $this->assertEquals('window', $article->getThirdPartySetting('gatsby', 'target'));
    $this->assertTrue($article->getThirdPartySetting('gatsby', 'iframe'));
    $this->assertEquals('https://example.com/__refresh', $gatsby_settings->get('preview_callback_url'));

  }

}
