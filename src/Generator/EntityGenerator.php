<?php

declare(strict_types=1);

namespace Drupal\devel_generate_custom_entities\Generator;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\devel_generate\DevelGenerateBase;
use Drupal\devel_generate_custom_entities\ValueObject\EntityGenerationOptions;

class EntityGenerator {
  protected EntityTypeManagerInterface $entityTypeManager;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->entityTypeManager = $entityTypeManager;
  }

  public function generateEntities(EntityGenerationOptions $options): void {
    $chunkSize = 100;

    for ($i = 0, $imax = $options->getNumberOfEntities(); $i < $imax; $i++) {
      $this->generateSingleEntity($options, $i + 1);

      if ($i % $chunkSize === 0) {
        usleep(5000);
      }
    }
  }

  public function generateEntitiesGenerator(EntityGenerationOptions $options): \Generator {
    $chunkSize = 100;

    for ($i = 0, $imax = $options->getNumberOfEntities(); $i < $imax; $i++) {
      $this->generateSingleEntity($options, $i + 1);

      if ($i % $chunkSize === 0) {
        usleep(5000);
      }

      yield 1;
    }
  }

  public function generateSingleEntity(EntityGenerationOptions $options, int $currentNumber): void {
    $storage = $this->entityTypeManager->getStorage($options->getEntityTypeId());

    $baseData = [
      'bundle' => $options->getBundleNames()[rand(0, count($options->getBundleNames()) - 1)],
      'uid'     => $options->getAuthorUid(),
      'label'    => str_replace('@num', (string) ($currentNumber), $options->getLabelPattern()),
      'status'  => 1,
      'created' => \Drupal::time()->getRequestTime(),
    ];

    $entity = $storage->create($baseData);
    DevelGenerateBase::populateFields($entity);
    $entity->save();
  }

}
