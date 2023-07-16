<?php

declare(strict_types=1);

namespace Drupal\devel_generate_custom_entities\Strategy;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\devel_generate_custom_entities\Deleter\EntityDeleter;
use Drupal\devel_generate_custom_entities\Generator\EntityGenerator;
use Drupal\devel_generate_custom_entities\ValueObject\EntityGenerationOptions;

class EntityGeneratorWithBatchStrategy implements EntityGeneratorStrategyInterface {
  use StringTranslationTrait;
  use DependencySerializationTrait;

  protected bool $drushBatch = FALSE;

  protected MessengerInterface $messenger;
  protected ExtensionPathResolver $extensionPathResolver;
  protected EntityGenerator $entityGenerator;
  protected EntityDeleter $entityDeleter;

  public function __construct(
    MessengerInterface $messenger,
    ExtensionPathResolver $extensionPathResolver,
    EntityGenerator $entityGenerator,
    EntityDeleter $entityDeleter,
  ) {
    $this->messenger = $messenger;
    $this->extensionPathResolver = $extensionPathResolver;
    $this->entityGenerator = $entityGenerator;
    $this->entityDeleter = $entityDeleter;
  }

  /**
   * {@inheritDoc}
   */
  public static function getIdentifier(): string {
    return 'with_batch';
  }

  public function generateEntities(EntityGenerationOptions $options): void {
    // If it is drushBatch then this operation is already run in the
    // DevelGenerateCustomEntitiesDevelGenerate::validateDrushParams().
    if (!$this->drushBatch) {
      // Setup the batch operations and save the variables.
      $operations[] = ['devel_generate_custom_entities_operation',
        [$this, 'batchContentPreEntity', $options],
      ];
    }

    // Add the deleteExisting operation.
    if ($options->isDeleteEntitiesBeforeCreation()) {
      $operations[] = ['devel_generate_custom_entities_operation',
        [$this, 'batchContentDeleteExisting', $options],
      ];
    }

    // Add the operations to create the nodes.
    for ($i = 0, $imax = $options->getNumberOfEntities(); $i < $imax; $i++) {
      $operations[] = ['devel_generate_custom_entities_operation',
        [$this, 'batchContentAddEntity', $options],
      ];
    }

    // Set the batch.
    $batch = [
      'title' => $this->t('Generating Entities'),
      'operations' => $operations,
      'finished' => 'devel_generate_custom_entities_batch_finished',
      'file' => $this->extensionPathResolver->getPath('module', 'devel_generate_custom_entities') . '/devel_generate_custom_entities.batch.inc',
    ];

    batch_set($batch);
    if ($this->drushBatch) {
      drush_backend_batch_process();
    }
  }

  public function deleteExistingEntities(string $entityTypeId): void {}

  public function batchContentPreEntity(EntityGenerationOptions $options, array &$context) {
    $context['results'] = $options->toArray();
    $context['results']['currentNumber'] = 0;
  }

  public function batchContentDeleteExisting(EntityGenerationOptions $options, array &$context) {
    if ($this->drushBatch) {
      $this->entityDeleter->deleteAllEntitiesOfType($options->getEntityTypeId());
    }
    else {
      $options = EntityGenerationOptions::fromArray($context['results']);
      $this->entityDeleter->deleteAllEntitiesOfType($options->getEntityTypeId());
    }

    $this->messenger->addMessage($this->t('Old entities have been deleted.'));
  }

  public function batchContentAddEntity(EntityGenerationOptions $options, array &$context) {
    if (!isset($context['results']['currentNumber'])) {
      $context['results']['currentNumber'] = 0;
    }
    $context['results']['currentNumber']++;

    if ($this->drushBatch) {
      $this->entityGenerator->generateSingleEntity($options, $context['results']['currentNumber']);
    }
    else {
      $options = EntityGenerationOptions::fromArray($context['results']);
      $this->entityGenerator->generateSingleEntity($options, $context['results']['currentNumber']);
    }
  }

  public function printBatchFinishedMessage(bool $success, int $numCreated): void {
    if ($success) {
      $this->messenger->addMessage($this->t('@num_entities created.', [
        '@num_entities' => $this->formatPlural($numCreated, '1 entity', '@count entities'),
      ]));
    }
    else {
      $this->messenger->addError($this->t('Finished with an error.'));
    }
  }

}
