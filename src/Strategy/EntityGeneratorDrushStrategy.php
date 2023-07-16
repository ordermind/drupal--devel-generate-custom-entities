<?php

declare(strict_types=1);

namespace Drupal\devel_generate_custom_entities\Strategy;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\devel_generate_custom_entities\Deleter\EntityDeleter;
use Drupal\devel_generate_custom_entities\Generator\EntityGenerator;
use Drupal\devel_generate_custom_entities\ValueObject\EntityGenerationOptions;

class EntityGeneratorDrushStrategy implements EntityGeneratorStrategyInterface {
  use StringTranslationTrait;

  protected MessengerInterface $messenger;
  protected EntityGenerator $entityGenerator;
  protected EntityDeleter $entityDeleter;

  public function __construct(
    MessengerInterface $messenger,
    EntityGenerator $entityGenerator,
    EntityDeleter $entityDeleter,
  ) {
    $this->messenger = $messenger;
    $this->entityGenerator = $entityGenerator;
    $this->entityDeleter = $entityDeleter;
  }

  public function generateEntities(EntityGenerationOptions $options): void {
    if ($options->isDeleteEntitiesBeforeCreation()) {
      $this->deleteExistingEntities($options->getEntityTypeId());
    }

    $this->createNewEntities($options);
  }

  protected function deleteExistingEntities(string $entityTypeId): void {
    $this->entityDeleter->deleteAllEntitiesOfType($entityTypeId);

    $this->messenger->addMessage($this->t('Old entities have been deleted.'));
  }

  protected function createNewEntities(EntityGenerationOptions $options): void {
    $this->entityGenerator->generateEntities($options);

    $this->messenger->addMessage($this->t('@num_entities created.', [
      '@num_entities' => $this->formatPlural($options->getNumberOfEntities(), '1 entity', '@count entities'),
    ]));
  }

}
