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

  public function generateEntities(EntityGenerationOptions $options): void {
    // If this is run via drush then this operation is already run in the
    // DevelGenerateCustomEntitiesDevelGenerate::validateDrushParams().
    if (!$options->isDrush()) {
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
    if ($options->isDrush()) {
      drush_backend_batch_process();
    }
  }

  public function batchContentPreEntity(EntityGenerationOptions $options, array|\ArrayObject &$context) {
    $context['results'] = $options->toArray();
    $context['results']['currentNumber'] = 0;
  }

  public function batchContentDeleteExisting(EntityGenerationOptions $options, array|\ArrayObject &$context) {
    if ($options->isDrush()) {
      $this->entityDeleter->deleteAllEntitiesOfType($options->getEntityTypeId());
    }
    else {
      $options = EntityGenerationOptions::fromArray($context['results']);
      $this->entityDeleter->deleteAllEntitiesOfType($options->getEntityTypeId());
    }

    $this->messenger->addMessage($this->t('Old entities have been deleted.'));
  }

  public function batchContentAddEntity(EntityGenerationOptions $options, array|\ArrayObject &$context) {
    if (!isset($context['results']['currentNumber'])) {
      $context['results']['currentNumber'] = 0;
    }
    $context['results']['currentNumber']++;

    if ($options->isDrush()) {
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