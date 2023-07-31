<?php

declare(strict_types=1);

namespace Drupal\Tests\devel_generate_custom_entities\Unit\Fixtures;

use Drupal\Core\Entity\EntityBase;

class DummyEntity extends EntityBase {
  protected int $id;
  protected string $label;

  protected string $bundle;
  protected ?int $uid;
  protected int $status;
  protected int $created;

  /**
   * {@inheritdoc}
   */
  public function bundle(): string {
    return $this->bundle;
  }

  public function uid(): ?int {
    return $this->uid;
  }

}
