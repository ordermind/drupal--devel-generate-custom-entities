<?php

declare(strict_types=1);

use Drupal\devel_generate_custom_entities\Strategy\EntityGeneratorDrushStrategy;
use Drupal\devel_generate_custom_entities\Strategy\EntityGeneratorStrategySelector;
use Drupal\devel_generate_custom_entities\Strategy\EntityGeneratorWebBatchStrategy;
use Drupal\devel_generate_custom_entities\Strategy\EntityGeneratorWebStrategy;
use Drupal\devel_generate_custom_entities\ValueObject\EntityGenerationOptions;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class EntityGeneratorStrategySelectorTest extends UnitTestCase {
  use ProphecyTrait;

  /**
   * @dataProvider provideTestSelectStrategy
   */
  public function testSelectStrategy(
    string $expectedClass,
    int $batchMinimumLimit,
    int $numberOfEntities,
    bool $drush
  ) {
    $webStrategy = $this->prophesize(EntityGeneratorWebStrategy::class)->reveal();
    $webBatchStrategy = $this->prophesize(EntityGeneratorWebBatchStrategy::class)->reveal();
    $drushStrategy = $this->prophesize(EntityGeneratorDrushStrategy::class)->reveal();

    $options = new EntityGenerationOptions(
      $drush,
      'entity_type',
      'label pattern',
      [],
      $numberOfEntities,
      TRUE,
      1
    );

    $selector = new EntityGeneratorStrategySelector($webStrategy, $webBatchStrategy, $drushStrategy);
    $strategy = $selector->selectStrategy($options, $batchMinimumLimit);

    $this->assertInstanceOf($expectedClass, $strategy);
  }

  public function provideTestSelectStrategy(): array {
    return [
      // Test scenarios where isDrush() is true
      [EntityGeneratorDrushStrategy::class, 50, 10, true],
      [EntityGeneratorDrushStrategy::class, 50, 100, true],

      // Test scenarios where isDrush() is false and numberOfEntities is below batchMinimumLimit
      [EntityGeneratorWebStrategy::class, 50, 10, false],
      [EntityGeneratorWebStrategy::class, 50, 49, false],

      // Test scenarios where isDrush() is false and numberOfEntities is equal to batchMinimumLimit
      [EntityGeneratorWebBatchStrategy::class, 50, 50, false],

      // Test scenarios where isDrush() is false and numberOfEntities is above batchMinimumLimit
      [EntityGeneratorWebBatchStrategy::class, 50, 51, false],
      [EntityGeneratorWebBatchStrategy::class, 50, 100, false],
    ];
  }
}