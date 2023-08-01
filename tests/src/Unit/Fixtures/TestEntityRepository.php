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

  public function fetchEntityIdsOfType(string $entityTypeId, int $offset = 0, int $chunkSize = 100): array {
    return array_map(
      fn (EntityInterface $entity) => $entity->id(),
      $this->fetchEntitiesOfType($entityTypeId, $offset, $chunkSize)
    );
  }

  public function fetchEntitiesOfType(string $entityTypeId, int $offset = 0, int $chunkSize = 100): array {
    $items = $this->storage->loadAll();

    return array_slice($items, $offset, $chunkSize);
  }

  public function fetchFirstEntityOfType(string $entityTypeId): ?EntityInterface {
    $items = $this->storage->loadAll();

    return reset($items);
  }

}
