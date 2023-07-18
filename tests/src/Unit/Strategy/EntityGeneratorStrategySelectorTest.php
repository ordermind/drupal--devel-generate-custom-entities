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

    $mockOptions = $this->prophesize(EntityGenerationOptions::class);
    $mockOptions->getNumberOfEntities()->willReturn($numberOfEntities);
    $mockOptions->isDrush()->willReturn($drush);
    $options = $mockOptions->reveal();

    $selector = new EntityGeneratorStrategySelector($webStrategy, $webBatchStrategy, $drushStrategy);
    $strategy = $selector->selectStrategy($options, $batchMinimumLimit);

    $this->assertInstanceOf($expectedClass, $strategy);
  }

  public function provideTestSelectStrategy(): array {
    return [
      [EntityGeneratorWebStrategy::class,       50, 10,   false],
      [EntityGeneratorWebBatchStrategy::class,  50, 100,  false],
      [EntityGeneratorDrushStrategy::class,     50, 10,   true],
      [EntityGeneratorDrushStrategy::class,     50, 100,  true],
    ];
  }
}