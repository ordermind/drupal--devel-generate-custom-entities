<?php

declare(strict_types=1);

namespace Drupal\devel_generate_custom_entities\Strategy;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\devel_generate_custom_entities\Deleter\EntityDeleter;
use Drupal\devel_generate_custom_entities\Generator\EntityGenerator;
use Drupal\devel_generate_custom_entities\ValueObject\EntityGenerationOptions;
use Drush\Drush;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;

class EntityGeneratorDrushStrategy implements EntityGeneratorStrategyInterface {
  use StringTranslationTrait;

  protected EntityGenerator $entityGenerator;
  protected EntityDeleter $entityDeleter;

  public function __construct(
    EntityGenerator $entityGenerator,
    EntityDeleter $entityDeleter,
  ) {
    $this->entityGenerator = $entityGenerator;
    $this->entityDeleter = $entityDeleter;
  }

  public function generateEntities(EntityGenerationOptions $options): void {
    $this->addOutputFormatterStyles();

    if ($options->isDeleteEntitiesBeforeCreation()) {
      $this->deleteExistingEntities($options->getEntityTypeId());
      Drush::output()->writeln('');
    }

    $this->createNewEntities($options);
  }

  protected function deleteExistingEntities(string $entityTypeId): void {
    $this->printNoticeMessage($this->t('Deleting old entities...')->__toString());
    $progressBar = new ProgressBar(Drush::output(), $this->entityDeleter->countEntitiesToDelete($entityTypeId));
    $progressBar->start();

    foreach ($this->entityDeleter->deleteAllEntitiesOfTypeGenerator($entityTypeId) as $deletedCount) {
      $progressBar->advance($deletedCount);
    }

    $progressBar->finish();
    Drush::output()->writeln('');

    $this->printSuccessMessage($this->t('Old entities have been deleted.')->__toString());
  }

  protected function createNewEntities(EntityGenerationOptions $options): void {
    $this->printNoticeMessage($this->t('Creating new entities...')->__toString());

    $progressBar = new ProgressBar(Drush::output(), $options->getNumberOfEntities());
    $progressBar->start();

    foreach ($this->entityGenerator->generateEntitiesGenerator($options) as $createdCount) {
      $progressBar->advance($createdCount);
    }

    $progressBar->finish();
    Drush::output()->writeln('');

    $this->printSuccessMessage($this->t('@num_entities created.', [
      '@num_entities' => $this->formatPlural($options->getNumberOfEntities(), '1 entity', '@count entities'),
    ])->__toString());
  }

  protected function addOutputFormatterStyles(): void {
    $successStyle = new OutputFormatterStyle('white', 'green', ['font-weight' => 'bold']);
    $output = Drush::output();
    $output->getFormatter()->setStyle('success', $successStyle);

    $noticeStyle = new OutputFormatterStyle('white', 'cyan', ['font-weight' => 'bold']);
    $output = Drush::output();
    $output->getFormatter()->setStyle('notice', $noticeStyle);
  }

  protected function printNoticeMessage(string $message): void {
    Drush::output()->writeln('<notice>[notice]</notice> ' . $message);
  }

  protected function printSuccessMessage(string $message): void {
    Drush::output()->writeln('<success>[success]</success> ' . $message);
  }

}
