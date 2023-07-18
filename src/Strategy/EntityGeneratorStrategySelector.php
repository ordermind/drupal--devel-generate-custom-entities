<?php

declare(strict_types=1);

namespace Drupal\devel_generate_custom_entities\Strategy;

use Drupal\devel_generate_custom_entities\ValueObject\EntityGenerationOptions;

class EntityGeneratorStrategySelector {
  protected EntityGeneratorWebStrategy $webStrategy;
  protected EntityGeneratorWebBatchStrategy $webBatchStrategy;
  protected EntityGeneratorDrushStrategy $drushStrategy;

  public function __construct(
    EntityGeneratorWebStrategy $webStrategy,
    EntityGeneratorWebBatchStrategy $webBatchStrategy,
    EntityGeneratorDrushStrategy $drushStrategy
  ) {
    $this->webStrategy = $webStrategy;
    $this->webBatchStrategy = $webBatchStrategy;
    $this->drushStrategy = $drushStrategy;
  }

  public function selectStrategy(EntityGenerationOptions $options, int $batchMinimumLimit): EntityGeneratorStrategyInterface {
    if ($options->isDrush()) {
      return $this->drushStrategy;
    }

    if ($options->getNumberOfEntities() >= $batchMinimumLimit) {
      return $this->webBatchStrategy;
    }

    return $this->webStrategy;
  }

}
