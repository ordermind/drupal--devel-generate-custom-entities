<?php

declare(strict_types=1);

namespace Drupal\devel_generate_custom_entities\ValueObject;

class EntityGenerationOptions {
  protected string $entityTypeId;
  protected string $labelPattern;
  protected array $bundleNames;
  protected int $numberOfEntities;
  protected bool $deleteEntitiesBeforeCreation;
  protected array $baseData;
  protected int $authorUid;

  /**
   * @param string[] $bundleNames The bundle ids that entities should be created for.
   * If there is more than one bundle, the entities will be spread out
   * evenly over the bundles.
   *
   * @param string $labelPattern A pattern for naming each entity with support
   * for placeholders.
   * Example: "Demo Content #@num"
   */
  public function __construct(
    string $entityTypeId,
    string $labelPattern,
    array $bundleNames,
    int $numberOfEntities,
    bool $deleteEntitiesBeforeCreation,
    int $authorUid
  ) {
    if ($numberOfEntities < 0) {
      throw new \InvalidArgumentException('The "numberOfEntities" parameter must be a positive integer');
    }

    $this->entityTypeId = $entityTypeId;
    $this->labelPattern = $labelPattern;
    $this->bundleNames = $bundleNames;
    $this->numberOfEntities = $numberOfEntities;
    $this->deleteEntitiesBeforeCreation = $deleteEntitiesBeforeCreation;
    $this->authorUid = $authorUid;
  }

  public static function fromArray(array $values): static {
    return new static(
      $values['entityTypeId'],
      $values['labelPattern'],
      $values['bundleNames'],
      $values['numberOfEntities'],
      $values['deleteEntitiesBeforeCreation'],
      $values['authorUid']
    );
  }

  public function getEntityTypeId(): string {
    return $this->entityTypeId;
  }

  public function getLabelPattern(): string {
    return $this->labelPattern;
  }

  public function getBundleNames(): array {
    return $this->bundleNames;
  }

  public function getNumberOfEntities(): int {
    return $this->numberOfEntities;
  }

  public function isDeleteEntitiesBeforeCreation(): bool {
    return $this->deleteEntitiesBeforeCreation;
  }

  public function getAuthorUid(): int {
    return $this->authorUid;
  }

  public function toArray(): array {
    return [
      'entityTypeId' => $this->getEntityTypeId(),
      'labelPattern' => $this->getLabelPattern(),
      'bundleNames' => $this->getBundleNames(),
      'numberOfEntities' => $this->getNumberOfEntities(),
      'deleteEntitiesBeforeCreation' => $this->isDeleteEntitiesBeforeCreation(),
      'authorUid' => $this->getAuthorUid(),
    ];
  }

}
