<?php

declare(strict_types=1);

namespace Drupal\Tests\devel_generate_custom_entities\Unit\Fixtures;

use Drupal\Core\Entity\EntityInterface;
use Drupal\tengstrom_general\Repository\EntityRepositoryInterface;
use Ordermind\DrupalTengstromShared\Test\Fixtures\EntityStorage\EntityArrayStorage;

class TestEntityRepository implements EntityRepositoryInterface {
  protected EntityArrayStorage $storage;

  public function __construct(EntityArrayStorage $storage) {
    $this->storage = $storage;
  }

  public function countEntitiesOfType(string $entityTypeId): int {
    return $this->storage->count();
  }

  public function fetchEntityIdsOfType(string $entityTypeId, int $chunkSize): \Generator {
    foreach ($this->fetchEntitiesOfType($entityTypeId, $chunkSize) as $entities) {
      yield  array_map(fn (EntityInterface $entity) => $entity->id(), $entities);
    }
  }

  public function fetchEntitiesOfType(string $entityTypeId, int $chunkSize): \Generator {
    $items = $this->storage->loadAll();

    for ($i = 0; $i < count($items); $i += $chunkSize) {
      yield array_slice($items, $i, $chunkSize);
    }
  }

  public function fetchFirstEntityOfType(string $entityTypeId): ?EntityInterface {
    $items = $this->storage->loadAll();

    return reset($items);
  }

}
