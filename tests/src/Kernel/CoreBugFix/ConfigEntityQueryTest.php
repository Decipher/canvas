<?php

declare(strict_types=1);

namespace Drupal\Tests\canvas\Kernel\CoreBugFix;

use Drupal\canvas\CoreBugFix\ConfigEntityQuery;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Tests\canvas\Kernel\CanvasKernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that a config entity query accepts a range given as strings.
 */
#[CoversClass(ConfigEntityQuery::class)]
#[Group('canvas')]
#[RunTestsInSeparateProcesses]
final class ConfigEntityQueryTest extends CanvasKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    foreach (['a', 'b', 'c'] as $id) {
      DateFormat::create(['id' => "canvas_test_$id", 'label' => $id, 'pattern' => 'Y'])->save();
    }
  }

  /**
   * @return \Generator<string, array{int|string|null, int|string|null, string[]}>
   */
  public static function provideRanges(): \Generator {
    yield 'integers' => [1, 1, ['canvas_test_b']];
    // JSON:API passes `page[offset]` on as the string from the URL.
    yield 'string offset' => ['1', 2, ['canvas_test_b', 'canvas_test_c']];
    yield 'string offset of zero' => ['0', 2, ['canvas_test_a', 'canvas_test_b']];
    yield 'string offset and length' => ['1', '1', ['canvas_test_b']];
    yield 'string offset, no length' => ['2', NULL, ['canvas_test_c']];
  }

  #[DataProvider('provideRanges')]
  public function testRange(int|string|null $start, int|string|null $length, array $expected): void {
    $query = $this->container->get('entity_type.manager')
      ->getStorage('date_format')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('id', 'canvas_test_', 'STARTS_WITH')
      ->sort('id')
      ->range($start, $length);
    self::assertInstanceOf(ConfigEntityQuery::class, $query);
    self::assertSame($expected, array_values($query->execute()));
  }

}
