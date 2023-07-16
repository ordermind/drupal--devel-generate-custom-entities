<?php

declare(strict_types=1);

namespace Drupal\devel_generate_custom_entities\Deleter;

use Drupal\Core\Entity\EntityTypeManagerInterface;

class EntityDeleter {

  protected EntityTypeManagerInterface $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  public function deleteAllEntitiesOfType(string $entityTypeId): void {
    $storage = $this->entityTypeManager->getStorage($entityTypeId);

    $chunkSize = 100;

    $entityQuery = $storage->getQuery()
      ->accessCheck(FALSE);

    while (
      $contentIds = $entityQuery
        ->range(0, $chunkSize)
        ->execute()
    ) {
      $entities = $storage->loadMultiple($contentIds);
      $storage->delete($entities);

      usleep(5000);
    }
  }

}
