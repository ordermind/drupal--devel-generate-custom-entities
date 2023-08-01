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

  public function getId(): int {
    return $this->id;
  }

  public function getLabel(): string {
    return $this->label;
  }

  public function getBundle(): string {
    return $this->bundle;
  }

  public function getUid(): ?int {
    return $this->uid;
  }

  public function getCreated(): int {
    return $this->created;
  }

  public function getStatus(): int {
    return $this->status;
  }

}
