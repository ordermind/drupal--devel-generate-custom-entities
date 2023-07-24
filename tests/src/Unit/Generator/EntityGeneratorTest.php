<?php

declare(strict_types=1);

namespace Drupal\Tests\devel_generate_custom_entities\Unit\Generator;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Cache\MemoryCache\MemoryCache;
use Drupal\Core\Cache\NullBackend;
use Drupal\Core\Entity\ContentEntityNullStorage;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityType;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\devel_generate_custom_entities\Generator\EntityGenerator;
use Drupal\devel_generate_custom_entities\ValueObject\EntityGenerationOptions;
use Drupal\Tests\devel_generate_custom_entities\Unit\Fixtures\DummyEntity;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class EntityGeneratorTest extends UnitTestCase {
  use ProphecyTrait;

  /**
   * @dataProvider provideTestGenerateSingleEntity
   */
  public function testGenerateSingleEntity(EntityGenerationOptions $options): void {
    $entityType = new EntityType([
      'id' => 'test_type',
      'class' => DummyEntity::class,
    ]);

    $mockEntityFieldManager = $this->prophesize(EntityFieldManagerInterface::class);
    $entityFieldManager = $mockEntityFieldManager->reveal();

    $cacheBackend = new NullBackend('entity');
    $memoryCache = new MemoryCache();

    $mockEntityTypeBundleInfo = $this->prophesize(EntityTypeBundleInfoInterface::class);
    $entityTypeBundleInfo = $mockEntityTypeBundleInfo->reveal();

    $storage = new ContentEntityNullStorage(
      $entityType,
      $entityFieldManager,
      $cacheBackend,
      $memoryCache,
      $entityTypeBundleInfo
    );

    $mockEntityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $mockEntityTypeManager
      ->getStorage(Argument::any())->willReturn($storage);
    $entityTypeManager = $mockEntityTypeManager->reveal();

    $mockTimeService = $this->prophesize(Time::class);
    $mockTimeService->getRequestTime()->willReturn(1690208874);
    $timeService = $mockTimeService->reveal();

    $entityGenerator = new EntityGenerator($entityTypeManager, $timeService);
    dump($entityGenerator->generateSingleEntity($options, 1));
  }

  public function provideTestGenerateSingleEntity(): array {
    return [
      [new EntityGenerationOptions(FALSE, 'test_type', 'Test Type #@num', ['bundle_1'], 0, FALSE, 1)],
    ];
  }

}
