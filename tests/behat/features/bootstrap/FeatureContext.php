<?php

use Behat\Behat\Tester\Exception\PendingException;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;


use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\MinkExtension\Context\RawMinkContext;

/**
 * Defines application features from the specific context.
 */
  class FeatureContext extends RawMinkContext {

  /**
   * Initializes context.
   *
   * Every scenario gets its own context instance.
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   */
  public function __construct() {
  }

 /**
   * @AfterStep
   */
  public function printLastResponseOnError(AfterStepScope $event)
  {
      if (!$event->getTestResult()->isPassed()) {
          $this->saveDebugScreenshot();
      }
  }

/**
   * @Then /^save screenshot$/
   */
  public function saveDebugScreenshot()
  {
    $filename = microtime(true).'.png';
    $path = 'var/behat_screenshots';
    $this->saveScreenshot($filename, $path);
  }
}