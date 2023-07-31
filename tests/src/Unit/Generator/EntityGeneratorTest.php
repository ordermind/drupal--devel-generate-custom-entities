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
use Ordermind\DrupalTengstromShared\Test\Concerns\AddsServicesToContainerDuringTest;
use Ordermind\DrupalTengstromShared\Test\Fixtures\EntityStorage\EntityArrayStorage;
use Ordermind\DrupalTengstromShared\Test\Fixtures\Factories\TestServiceContainerFactory;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class EntityGeneratorTest extends UnitTestCase {
  use ProphecyTrait;
  use AddsServicesToContainerDuringTest;

  protected function setUp(): void {
    parent::setUp();

    $containerFactory = new TestServiceContainerFactory();
    $container = $containerFactory->createWithBasicServices();

    \Drupal::setContainer($container);
  }

  public function testGenerateSingleEntity(): void {
    $options = new EntityGenerationOptions(FALSE, 'test_type', 'Test Type #@num', ['bundle_1'], 0, FALSE, 1);

    $entityType = new EntityType([
      'id' => $options->getEntityTypeId(),
      'class' => DummyEntity::class,
      'entity_keys' => [
        'id' => 'id',
        'label' => 'label',
      ],
    ]);

    $storage = EntityArrayStorage::createInstance(\Drupal::getContainer(), $entityType);

    $mockEntityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $mockEntityTypeManager->getStorage(Argument::any())->willReturn($storage);
    $mockEntityTypeManager->getDefinition($entityType->id())->willReturn($entityType);
    $entityTypeManager = $mockEntityTypeManager->reveal();

    $mockTimeService = $this->prophesize(Time::class);
    $mockTimeService->getRequestTime()->willReturn(1690208874);
    $timeService = $mockTimeService->reveal();

    $this->addMultipleServices([
      'entity_type.manager' => $entityTypeManager,
    ]);

    $this->assertEquals($storage->count(), 0);

    $entityGenerator = new EntityGenerator($entityTypeManager, $timeService);
    $entityGenerator->generateSingleEntity($options, 1);

    $this->assertEquals($storage->count(), 1);

    $entity = $storage->load(1);
    $this->assertInstanceOf(DummyEntity::class, $entity);
    /** @var \Drupal\Tests\devel_generate_custom_entities\Unit\Fixtures\DummyEntity $entity */

    $this->assertSame($options->getEntityTypeId(), $entity->getEntityType()->id());
    $this->assertSame($options->getEntityTypeId(), $entity->getEntityTypeId());
    $this->assertSame(str_replace('@num', (string) $entity->id(), $options->getLabelPattern()), $entity->label());
    $this->assertSame($options->getBundleNames(), [$entity->bundle()]);
    $this->assertSame($options->getAuthorUid(), $entity->uid());
  }

}
