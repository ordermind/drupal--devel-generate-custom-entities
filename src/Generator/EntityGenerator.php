<?php

declare(strict_types=1);

namespace Drupal\devel_generate_custom_entities\Generator;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\devel_generate\DevelGenerateBase;
use Drupal\devel_generate_custom_entities\ValueObject\EntityGenerationOptions;

class EntityGenerator {
  protected EntityTypeManagerInterface $entityTypeManager;
  protected TimeInterface $timeService;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    TimeInterface $timeService
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->timeService = $timeService;
  }

  /**
   * Generates entities all in one go.
   */
  public function generateEntities(EntityGenerationOptions $options): void {
    $chunkSize = 100;

    for ($i = 0, $imax = $options->getNumberOfEntities(); $i < $imax; $i++) {
      $this->generateSingleEntity($options, $i + 1);

      if ($i % $chunkSize === 0) {
        usleep(5000);
      }
    }
  }

  /**
   * Generates entities using a generator.
   *
   * @return \Generator|int[] number of entities generated in the current iteration of the loop
   */
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

  /**
   * Generates a single entity.
   */
  public function generateSingleEntity(EntityGenerationOptions $options, int $currentNumber): void {
    $storage = $this->entityTypeManager->getStorage($options->getEntityTypeId());

    $baseData = [
      'bundle' => $options->getBundleNames()[rand(0, count($options->getBundleNames()) - 1)],
      'uid'     => $options->getAuthorUid(),
      'label'    => str_replace('@num', (string) ($currentNumber), $options->getLabelPattern()),
      'status'  => 1,
      'created' => $this->timeService->getRequestTime(),
    ];

    $entity = $storage->create($baseData);
    DevelGenerateBase::populateFields($entity);
    $entity->save();
  }

}
