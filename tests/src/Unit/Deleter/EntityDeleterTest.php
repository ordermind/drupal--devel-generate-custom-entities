<?php

declare(strict_types=1);

namespace Drupal\Tests\devel_generate_custom_entities\Unit\Deleter;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\devel_generate_custom_entities\Deleter\EntityDeleter;
use Drupal\Tests\devel_generate_custom_entities\Unit\Fixtures\DummyEntity;
use Drupal\Tests\UnitTestCase;
use Ordermind\DrupalTengstromShared\Test\Fixtures\EntityStorage\EntityArrayStorage;
use Ordermind\DrupalTengstromShared\Test\Fixtures\Factories\TestServiceContainerFactory;
use Prophecy\PhpUnit\ProphecyTrait;

use function Ordermind\Helpers\Misc\xrange;

class EntityDeleterTest extends UnitTestCase {
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

  protected function createEntities(EntityStorageInterface $storage, int $number): void {
    foreach (xrange(1, $number) as $id) {
      $baseData = [
        'bundle' => 'bundle_1',
        'uid'     => 1,
        'label'    => "Test Type #{$id}",
        'status'  => 1,
        'created' => 1690208874,
      ];

      $entity = $storage->create($baseData);
      $entity->save();
    }
  }

  public function testCountEntitiesToDelete(): void {
    $expectedResult = 200;
    $entityTypeManager = \Drupal::service('entity_type.manager');
    $storage = $entityTypeManager->getStorage('test_type');
    $this->createEntities($storage, $expectedResult);

    $entityDeleter = new EntityDeleter($entityTypeManager);
    $result = $entityDeleter->countEntitiesToDelete('test_type');

    $this->assertSame($expectedResult, $result);
  }

}
