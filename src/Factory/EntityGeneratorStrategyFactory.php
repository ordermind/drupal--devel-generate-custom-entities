<?php

declare(strict_types=1);

namespace Drupal\devel_generate_custom_entities\Factory;

use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\devel_generate_custom_entities\Deleter\EntityDeleter;
use Drupal\devel_generate_custom_entities\Generator\EntityGenerator;
use Drupal\devel_generate_custom_entities\Strategy\EntityGeneratorStrategyInterface;
use Drupal\devel_generate_custom_entities\Strategy\EntityGeneratorWithBatchStrategy;
use Drupal\devel_generate_custom_entities\Strategy\EntityGeneratorWithoutBatchStrategy;

class EntityGeneratorStrategyFactory {
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

  public function createStrategy(string $strategyName): EntityGeneratorStrategyInterface {
    if (EntityGeneratorWithBatchStrategy::getIdentifier() === $strategyName) {
      return new EntityGeneratorWithBatchStrategy(
        $this->messenger,
        $this->extensionPathResolver,
        $this->entityGenerator,
        $this->entityDeleter
      );
    }

    if (EntityGeneratorWithoutBatchStrategy::getIdentifier() === $strategyName) {
      return new EntityGeneratorWithoutBatchStrategy(
        $this->messenger,
        $this->entityGenerator,
        $this->entityDeleter
      );
    }

    throw new \InvalidArgumentException("The strategy name \"{$strategyName}\" is not supported by this factory");
  }

}
