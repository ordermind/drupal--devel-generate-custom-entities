<?php

declare(strict_types=1);

namespace Drupal\devel_generate_custom_entities\Deleter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\tengstrom_general\Repository\EntityRepository;

class EntityDeleter {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected EntityRepository $repository;

  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityRepository $repository) {
    $this->entityTypeManager = $entityTypeManager;
    $this->repository = $repository;
  }

  public function deleteAllEntitiesOfType(string $entityTypeId): void {
    $storage = $this->entityTypeManager->getStorage($entityTypeId);
    $chunkSize = 100;

    foreach ($this->repository->fetchEntitiesOfType($entityTypeId, $chunkSize) as $entities) {
      $storage->delete($entities);

      usleep(5000);
    }
  }

  public function deleteAllEntitiesOfTypeGenerator(string $entityTypeId): \Generator {
    $storage = $this->entityTypeManager->getStorage($entityTypeId);

    $currentIndex = 0;
    $chunkSize = 100;

    foreach ($this->repository->fetchEntitiesOfType($entityTypeId, $chunkSize) as $entities) {
      $entityCount = count($entities);
      $storage->delete($entities);
      $currentIndex += $chunkSize;

      usleep(5000);

      yield $entityCount;
    }
  }

  public function deleteFirstEntityOfType(string $entityTypeId): void {
    $storage = $this->entityTypeManager->getStorage($entityTypeId);
    $entity = $this->repository->fetchFirstEntityOfType($entityTypeId);

    if (!$entity) {
      return;
    }

    $storage->delete([$entity]);
  }

}
