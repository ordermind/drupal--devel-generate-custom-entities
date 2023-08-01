<?php

declare(strict_types=1);

namespace Drupal\Tests\devel_generate_custom_entities\Unit\Generator;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Entity\EntityType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\devel_generate_custom_entities\Generator\EntityGenerator;
use Drupal\devel_generate_custom_entities\ValueObject\EntityGenerationOptions;
use Drupal\Tests\devel_generate_custom_entities\Unit\Fixtures\DummyEntity;
use Drupal\Tests\UnitTestCase;
use Ordermind\DrupalTengstromShared\Test\Fixtures\EntityStorage\EntityArrayStorage;
use Ordermind\DrupalTengstromShared\Test\Fixtures\Factories\TestServiceContainerFactory;
use Prophecy\PhpUnit\ProphecyTrait;

class EntityGeneratorTest extends UnitTestCase {
  use ProphecyTrait;

  protected function setUp(): void {
    parent::setUp();

    $entityType = new EntityType([
      'id' => 'test_type',
      'class' => DummyEntity::class,
      'entity_keys' => [
        'id' => 'id',
        'label' => 'label',
      ],
    ]);

    $containerFactory = new TestServiceContainerFactory();
    $container = $containerFactory->createWithBasicServices();
    \Drupal::setContainer($container);

    $storage = EntityArrayStorage::createInstance($container, $entityType);

    $mockEntityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $mockEntityTypeManager->getStorage($entityType->id())->willReturn($storage);
    $mockEntityTypeManager->getDefinition($entityType->id())->willReturn($entityType);
    $entityTypeManager = $mockEntityTypeManager->reveal();

    $container->set('entity_type.manager', $entityTypeManager);
  }

  /**
   * @test
   * @dataProvider provideNumberOfEntities
   *
   * phpcs:disable Drupal.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
   */
  public function generateEntities_saves_multiple_entities_in_storage(int $numberOfEntities): void {
    $expectedTime = 1690208874;

    $options = new EntityGenerationOptions(FALSE, 'test_type', 'Test Type #@num', ['bundle_1'], $numberOfEntities, FALSE, 1);

    $mockTimeService = $this->prophesize(Time::class);
    $mockTimeService->getRequestTime()->willReturn($expectedTime);
    $timeService = $mockTimeService->reveal();

    $entityTypeManager = \Drupal::service('entity_type.manager');
    $storage = $entityTypeManager->getStorage($options->getEntityTypeId());
    /** @var \Ordermind\DrupalTengstromShared\Test\Fixtures\EntityStorage\EntityArrayStorage $storage */

    $this->assertEquals(0, $storage->count());

    $entityGenerator = new EntityGenerator($entityTypeManager, $timeService);
    $entityGenerator->generateEntities($options);

    $this->assertEquals($numberOfEntities, $storage->count());
  }

  /**
   * @test
   * @dataProvider provideNumberOfEntities
   *
   * phpcs:disable Drupal.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
   */
  public function generateEntitiesGenerator_saves_multiple_entities_in_database(int $numberOfEntities): void {
    $expectedTime = 1690208874;

    $options = new EntityGenerationOptions(FALSE, 'test_type', 'Test Type #@num', ['bundle_1'], $numberOfEntities, FALSE, 1);

    $mockTimeService = $this->prophesize(Time::class);
    $mockTimeService->getRequestTime()->willReturn($expectedTime);
    $timeService = $mockTimeService->reveal();

    $entityTypeManager = \Drupal::service('entity_type.manager');
    $storage = $entityTypeManager->getStorage($options->getEntityTypeId());
    /** @var \Ordermind\DrupalTengstromShared\Test\Fixtures\EntityStorage\EntityArrayStorage $storage */

    $this->assertEquals(0, $storage->count());

    $entityGenerator = new EntityGenerator($entityTypeManager, $timeService);

    $totalGeneratedCount = 0;
    foreach ($entityGenerator->generateEntitiesGenerator($options) as $iterationGeneratedCount) {
      $totalGeneratedCount += $iterationGeneratedCount;
    }

    $this->assertEquals($numberOfEntities, $totalGeneratedCount);
    $this->assertEquals($numberOfEntities, $storage->count());
  }

  public function provideNumberOfEntities(): array {
    return [
      [0],
      [1],
      [50],
      [100],
      [150],
      [200],
    ];

  }

  /**
   * @test
   *
   * phpcs:disable Drupal.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
   */
  public function generateSingleEntity_saves_single_entity_in_storage(): void {
    $expectedLabel = 'Test Type #1';
    $expectedTime = 1690208874;

    $options = new EntityGenerationOptions(FALSE, 'test_type', 'Test Type #@num', ['bundle_1'], 0, FALSE, 1);

    $mockTimeService = $this->prophesize(Time::class);
    $mockTimeService->getRequestTime()->willReturn($expectedTime);
    $timeService = $mockTimeService->reveal();

    $entityTypeManager = \Drupal::service('entity_type.manager');
    $storage = $entityTypeManager->getStorage($options->getEntityTypeId());
    /** @var \Ordermind\DrupalTengstromShared\Test\Fixtures\EntityStorage\EntityArrayStorage $storage */

    $this->assertEquals(0, $storage->count());

    $entityGenerator = new EntityGenerator($entityTypeManager, $timeService);
    $entityGenerator->generateSingleEntity($options, 1);

    $this->assertEquals(1, $storage->count());

    $entity = $storage->load(1);
    $this->assertInstanceOf(DummyEntity::class, $entity);
    /** @var \Drupal\Tests\devel_generate_custom_entities\Unit\Fixtures\DummyEntity $entity */

    $this->assertSame($options->getEntityTypeId(), $entity->getEntityType()->id());
    $this->assertSame($options->getEntityTypeId(), $entity->getEntityTypeId());
    $this->assertSame(1, $entity->getId());
    $this->assertSame($expectedLabel, $entity->getLabel());
    $this->assertSame($options->getBundleNames(), [$entity->getBundle()]);
    $this->assertSame($options->getAuthorUid(), $entity->getUid());
    $this->assertSame(1, $entity->getStatus());
    $this->assertSame($expectedTime, $entity->getCreated());
  }

}
