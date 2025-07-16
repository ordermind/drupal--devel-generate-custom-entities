<?php

declare(strict_types=1);

namespace Drupal\Tests\devel_generate_custom_entities\Unit\Deleter;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\devel_generate_custom_entities\Deleter\EntityDeleter;
use Drupal\Tests\tengstrom_general\Unit\Fixtures\TestEntityRepository;
use Drupal\Tests\UnitTestCase;
use Ordermind\DrupalTengstromShared\Test\Fixtures\Entity\DummyEntity;
use Ordermind\DrupalTengstromShared\Test\Fixtures\EntityStorage\ContentEntityArrayStorage;
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

    $storage = ContentEntityArrayStorage::createInstance($container, $entityType);

    $mockEntityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $mockEntityTypeManager->getStorage($entityType->id())->willReturn($storage);
    $mockEntityTypeManager->getDefinition($entityType->id())->willReturn($entityType);
    $entityTypeManager = $mockEntityTypeManager->reveal();

    $container->set('entity_type.manager', $entityTypeManager);
  }

  protected function createEntities(EntityStorageInterface $storage, int $number): void {
    if (!$number) {
      return;
    }

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

  /**
   * @test
   * @dataProvider provideNumberOfEntities
   *
   * phpcs:disable Drupal.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
   */
  public function deleteAllEntitiesOfType_deletes_all_entities_in_storage(int $numberOfEntities): void {
    $entityTypeManager = \Drupal::service('entity_type.manager');
    $storage = $entityTypeManager->getStorage('test_type');
    $this->createEntities($storage, $numberOfEntities);

    $repository = new TestEntityRepository($entityTypeManager);
    $deleter = new EntityDeleter($entityTypeManager, $repository);

    $this->assertSame($numberOfEntities, $repository->countEntitiesOfType('test_type'));

    $deleter->deleteAllEntitiesOfType('test_type');

    $this->assertSame(0, $repository->countEntitiesOfType('test_type'));
  }

  /**
   * @test
   * @dataProvider provideNumberOfEntities
   *
   * phpcs:disable Drupal.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
   */
  public function deleteAllEntitiesOfTypeGenerator_deletes_all_entities_in_storage(int $numberOfEntities): void {
    $entityTypeManager = \Drupal::service('entity_type.manager');
    $storage = $entityTypeManager->getStorage('test_type');
    $this->createEntities($storage, $numberOfEntities);

    $repository = new TestEntityRepository($entityTypeManager);
    $deleter = new EntityDeleter($entityTypeManager, $repository);

    $this->assertSame($numberOfEntities, $repository->countEntitiesOfType('test_type'));

    iterator_to_array($deleter->deleteAllEntitiesOfTypeGenerator('test_type'));

    $this->assertSame(0, $repository->countEntitiesOfType('test_type'));
  }

  public static function provideNumberOfEntities(): array {
    return [
      [0],
      [62],
      [99],
      [100],
      [101],
      [130],
      [200],
      [203],
    ];
  }

  public function testDeleteFirstEntityOfType(): void {
    $entityTypeManager = \Drupal::service('entity_type.manager');
    $storage = $entityTypeManager->getStorage('test_type');
    $this->createEntities($storage, 50);

    $repository = new TestEntityRepository($entityTypeManager);
    $deleter = new EntityDeleter($entityTypeManager, $repository);

    $this->assertSame(50, $repository->countEntitiesOfType('test_type'));

    $deleter->deleteFirstEntityOfType('test_type');

    $this->assertSame(49, $repository->countEntitiesOfType('test_type'));
    $firstEntity = $repository->fetchFirstEntityOfType('test_type');
    $this->assertSame(2, $firstEntity->id());
  }

}
