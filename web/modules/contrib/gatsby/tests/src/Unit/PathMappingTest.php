<?php

namespace Drupal\Tests\gatsby\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\gatsby\PathMapping;

/**
 * Defines a test for GatsbyPathMapping.
 *
 * @group gatsby
 *
 * @covers \Drupal\gatsby\PathMapping
 */
class PathMappingTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected $pathmapping;

  /**
   * Tests GatsbyPathMapping::parsePathMapping.
   *
   * @dataProvider providerParsePathMapping
   */
  public function testParsePathMapping(string $mapping, array $expected) {
    $this->assertEquals($expected, PathMapping::parsePathMapping($mapping));
  }

  /**
   * Data provider for testParsePathMapping.
   *
   * @return array
   *   Test cases.
   */
  public function providerParsePathMapping() : array {
    return [
      'empty' => ['', []],
      'single value' => ['/foo|/bar', ['/foo' => '/bar']],
      'complex nested path' => [
        '/foo/bar/whizz|/bar/biz/bang',
        ['/foo/bar/whizz' => '/bar/biz/bang'],
      ],
      'multiple values' => [
        "/foo|/bar\n/biz|/bang",
        ['/foo' => '/bar', '/biz' => '/bang'],
      ],
      'empty line in middle' => [
        "/foo|/bar\n\n/biz|/bang",
        ['/foo' => '/bar', '/biz' => '/bang'],
      ],
      'empty line at end' => [
        "/foo|/bar\n/biz|/bang\n",
        ['/foo' => '/bar', '/biz' => '/bang'],
      ],
      'empty line at start' => [
        "\n/foo|/bar\n/biz|/bang",
        ['/foo' => '/bar', '/biz' => '/bang'],
      ],
    ];
  }

}
