<?php

declare(strict_types=1);

namespace Drupal\devel_generate_custom_entities\Strategy;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\devel_generate_custom_entities\Deleter\EntityDeleter;
use Drupal\devel_generate_custom_entities\Generator\EntityGenerator;
use Drupal\devel_generate_custom_entities\ValueObject\EntityGenerationOptions;
use Drupal\tengstrom_general\Drush\Output\DrushMessenger;
use Symfony\Component\Console\Helper\ProgressBar;

class EntityGeneratorDrushStrategy implements EntityGeneratorStrategyInterface {
  use StringTranslationTrait;

  protected EntityGenerator $entityGenerator;
  protected EntityDeleter $entityDeleter;
  protected DrushMessenger $drushMessenger;

  public function __construct(
    EntityGenerator $entityGenerator,
    EntityDeleter $entityDeleter,
    DrushMessenger $drushMessenger
  ) {
    $this->entityGenerator = $entityGenerator;
    $this->entityDeleter = $entityDeleter;
    $this->drushMessenger = $drushMessenger;
  }

  public function generateEntities(EntityGenerationOptions $options): void {
    if ($options->isDeleteEntitiesBeforeCreation()) {
      $this->deleteExistingEntities($options->getEntityTypeId());
      $this->drushMessenger->blankLine();
    }

    $this->createNewEntities($options);
  }

  protected function deleteExistingEntities(string $entityTypeId): void {
    $this->drushMessenger->notice($this->t('Deleting old entities...')->__toString());
    $progressBar = new ProgressBar($this->drushMessenger->getOutput(), $this->entityDeleter->countEntitiesToDelete($entityTypeId));
    $progressBar->start();

    foreach ($this->entityDeleter->deleteAllEntitiesOfTypeGenerator($entityTypeId) as $deletedCount) {
      $progressBar->advance($deletedCount);
    }

    $progressBar->finish();
    $this->drushMessenger->blankLine();

    $this->drushMessenger->success($this->t('Old entities have been deleted.')->__toString());
  }

  protected function createNewEntities(EntityGenerationOptions $options): void {
    $this->drushMessenger->notice($this->t('Creating new entities...')->__toString());

    $progressBar = new ProgressBar($this->drushMessenger->getOutput(), $options->getNumberOfEntities());
    $progressBar->start();

    foreach ($this->entityGenerator->generateEntitiesGenerator($options) as $createdCount) {
      $progressBar->advance($createdCount);
    }

    $progressBar->finish();
    $this->drushMessenger->blankLine();

    $this->drushMessenger->success($this->t('@num_entities created.', [
      '@num_entities' => $this->formatPlural($options->getNumberOfEntities(), '1 entity', '@count entities'),
    ])->__toString());
  }

}
