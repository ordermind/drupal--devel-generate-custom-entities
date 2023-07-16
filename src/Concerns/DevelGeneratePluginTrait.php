<?php

declare(strict_types=1);

namespace Drupal\devel_generate_custom_entities\Concerns;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\devel_generate_custom_entities\Factory\EntityGeneratorStrategyFactory;
use Drupal\devel_generate_custom_entities\Strategy\EntityGeneratorStrategyInterface;
use Drupal\devel_generate_custom_entities\Strategy\EntityGeneratorWithBatchStrategy;
use Drupal\devel_generate_custom_entities\Strategy\EntityGeneratorWithoutBatchStrategy;
use Drupal\devel_generate_custom_entities\ValueObject\EntityGenerationOptions;

trait DevelGeneratePluginTrait {

  abstract protected function getEntityTypeId(): string;

  /**
   * @return string[] array of bundles names to generate entities for.
   */
  abstract protected function getBundleNames(): array;

  /**
   * @return string The label pattern as described in the EntityGenerationOptions.
   *
   * @see EntityGenerationOptions
   */
  abstract protected function getLabelPattern(): string;

  abstract protected function getCurrentUser(): AccountProxyInterface;

  abstract protected function getStrategyFactory(): EntityGeneratorStrategyFactory;

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form['num'] = [
      '#type' => 'textfield',
      '#title' => $this->t('How many entities would you like to generate?'),
      '#default_value' => $this->getSetting('num'),
      '#size' => 10,
    ];

    $form['delete_existing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete all entities before generating new ones.'),
      '#default_value' => $this->getSetting('delete_existing'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function generateElements(array $values): void {
    $num = (int) $values['num'];
    $deleteExisting = (bool) $values['delete_existing'];

    $generationOptions = new EntityGenerationOptions(
      $this->getEntityTypeId(),
      $this->getLabelPattern(),
      $this->getBundleNames(),
      $num,
      $deleteExisting,
      (int) $this->getCurrentUser()->id()
    );

    $strategy = $this->getStrategy($num);

    $strategy->generateEntities($generationOptions);
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrushParams(array $args, array $options = []): array {
    $values = [
      'num' => $options['num'],
      'deleteExisting' => $options['deleteExisting'],
    ];

    return $values;
  }

  protected function getStrategy(int $num): EntityGeneratorStrategyInterface {
    if ($num > $this->getSetting('batch_minimum_limit')) {
      return $this->getStrategyFactory()->createStrategy(EntityGeneratorWithBatchStrategy::getIdentifier());
    }

    return $this->getStrategyFactory()->createStrategy(EntityGeneratorWithoutBatchStrategy::getIdentifier());
  }

}
