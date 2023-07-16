<?php

declare(strict_types=1);

namespace Drupal\devel_generate_custom_entities\Strategy;

use Drupal\devel_generate_custom_entities\ValueObject\EntityGenerationOptions;

interface EntityGeneratorStrategyInterface {

  public function generateEntities(EntityGenerationOptions $options): void;

}
