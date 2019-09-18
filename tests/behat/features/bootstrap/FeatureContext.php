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
// class FeatureContext extends RawDrupalContext implements SnippetAcceptingContext {
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
    echo "-------------FEATURECONTEXT: printLastResponseOnError!-------";
      if (!$event->getTestResult()->isPassed()) {
        echo "-------------FEATURECONTEXT: printLastResponseOnError! DIDN'T PASS-------";
          $this->saveDebugScreenshot();
      }
  }

/**
   * @Then /^save screenshot$/
   */
  public function saveDebugScreenshot()
  {
    echo "-------------FEATURECONTEXT: saveDebugScreenshot! -------";
    $driver = $this->getSession()->getDriver();

    // if (!$driver instanceof Selenium2Driver) {
    //   echo "-------------FEATURECONTEXT: driver instanceof Selenium2Driver! -------";
    //     return;
    // }

    // if (!getenv('BEHAT_SCREENSHOTS')) {
    //   echo "-------------FEATURECONTEXT: NOT BEHAT_SCREENSHOTS! -------";
    //     return;
    // }

    echo "-------------FEATURECONTEXT: GETTING FILENAME AND PATH ! -------";
    $filename = microtime(true).'.png';
    // $path = $this->getContainer()
    //     ->getParameter('kernel.root_dir').'/../behat_screenshots';

    // if (!file_exists($path)) {
    //     mkdir($path);
    // }

    echo "-------------FEATURECONTEXT: SAVING SCREENSHOTS! -------";
    // echo("----- saving screenshot" . $filename . "to" . $path);
    echo("----- saving screenshot" . $filename);

    // $this->saveScreenshot($filename, $path);

    $this->saveScreenshot($filename, 'var/behat_screenshots');
  }
}