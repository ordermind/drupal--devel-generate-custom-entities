<?php

declare(strict_types=1);

namespace Drupal\devel_generate_custom_entities\Strategy;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\devel_generate_custom_entities\Deleter\EntityDeleter;
use Drupal\devel_generate_custom_entities\Generator\EntityGenerator;
use Drupal\devel_generate_custom_entities\ValueObject\EntityGenerationOptions;
use Drupal\tengstrom_general\Drush\Output\StyledDrushOutput;
use Symfony\Component\Console\Helper\ProgressBar;

class EntityGeneratorDrushStrategy implements EntityGeneratorStrategyInterface {
  use StringTranslationTrait;

  protected EntityGenerator $entityGenerator;
  protected EntityDeleter $entityDeleter;
  protected StyledDrushOutput $drushOutput;

  public function __construct(
    EntityGenerator $entityGenerator,
    EntityDeleter $entityDeleter,
    StyledDrushOutput $drushOutput
  ) {
    $this->entityGenerator = $entityGenerator;
    $this->entityDeleter = $entityDeleter;
    $this->drushOutput = $drushOutput;
  }

  public function generateEntities(EntityGenerationOptions $options): void {
    if ($options->isDeleteEntitiesBeforeCreation()) {
      $this->deleteExistingEntities($options->getEntityTypeId());
      $this->drushOutput->blankLine();
    }

    $this->createNewEntities($options);
  }

  protected function deleteExistingEntities(string $entityTypeId): void {
    $this->drushOutput->notice($this->t('Deleting old entities...')->__toString());
    $progressBar = new ProgressBar($this->drushOutput->getInstance(), $this->entityDeleter->countEntitiesToDelete($entityTypeId));
    $progressBar->start();

    foreach ($this->entityDeleter->deleteAllEntitiesOfTypeGenerator($entityTypeId) as $deletedCount) {
      $progressBar->advance($deletedCount);
    }

    $progressBar->finish();
    $this->drushOutput->blankLine();

    $this->drushOutput->success($this->t('Old entities have been deleted.')->__toString());
  }

  protected function createNewEntities(EntityGenerationOptions $options): void {
    $this->drushOutput->notice($this->t('Creating new entities...')->__toString());

    $progressBar = new ProgressBar($this->drushOutput->getInstance(), $options->getNumberOfEntities());
    $progressBar->start();

    foreach ($this->entityGenerator->generateEntitiesGenerator($options) as $createdCount) {
      $progressBar->advance($createdCount);
    }

    $progressBar->finish();
    $this->drushOutput->blankLine();

    $this->drushOutput->success($this->t('@num_entities created.', [
      '@num_entities' => $this->formatPlural($options->getNumberOfEntities(), '1 entity', '@count entities'),
    ])->__toString());
  }

}
