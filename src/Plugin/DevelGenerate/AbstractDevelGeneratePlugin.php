<?php

declare(strict_types=1);

namespace Drupal\devel_generate_custom_entities\Plugin\DevelGenerate;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\devel_generate\DevelGenerateBase;
use Drupal\devel_generate_custom_entities\Strategy\EntityGeneratorStrategyInterface;
use Drupal\devel_generate_custom_entities\Strategy\EntityGeneratorWithBatchStrategy;
use Drupal\devel_generate_custom_entities\Strategy\EntityGeneratorWithoutBatchStrategy;
use Drupal\devel_generate_custom_entities\ValueObject\EntityGenerationOptions;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractDevelGeneratePlugin extends DevelGenerateBase implements ContainerFactoryPluginInterface {
  protected EntityGeneratorWithBatchStrategy $withBatchStrategy;
  protected EntityGeneratorWithoutBatchStrategy $withoutBatchStrategy;
  protected AccountProxyInterface $currentUser;
  protected EntityTypeBundleInfoInterface $bundleInfo;

  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    EntityGeneratorWithBatchStrategy $withBatchStrategy,
    EntityGeneratorWithoutBatchStrategy $withoutBatchStrategy,
    AccountProxyInterface $currentUser,
    EntityTypeBundleInfoInterface $bundleInfo
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->withBatchStrategy = $withBatchStrategy;
    $this->withoutBatchStrategy = $withoutBatchStrategy;
    $this->currentUser = $currentUser;
    $this->bundleInfo = $bundleInfo;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('devel_generate_custom_entities.strategy_with_batch'),
      $container->get('devel_generate_custom_entities.strategy_without_batch'),
      $container->get('current_user'),
      $container->get('entity_type.bundle.info')
    );
  }

  abstract protected function getEntityTypeId(): string;

  /**
   * @return string The label pattern as described in the EntityGenerationOptions.
   *
   * @see EntityGenerationOptions
   */
  abstract protected function getLabelPattern(): string;

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
  public function generate(array $values): void {
    $num = (int) $values['num'];
    $deleteExisting = (bool) $values['delete_existing'];
    $drush = !empty($values['drush']);

    $generationOptions = new EntityGenerationOptions(
      $drush,
      $this->getEntityTypeId(),
      $this->getLabelPattern(),
      $this->getBundleNames(),
      $num,
      $deleteExisting,
      (int) $this->currentUser->id() ?: 1
    );

    $strategy = $this->getStrategy($num);

    $strategy->generateEntities($generationOptions);
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrushParams(array $args, array $options = []): array {
    $values = [
      'num' => $args['num'],
      'delete_existing' => $options['delete-existing'],
      'drush' => TRUE,
    ];

    return $values;
  }

  /**
   * @return string[] array of bundles names to generate entities for.
   */
  protected function getBundleNames(): array {
    $bundles = $this->bundleInfo->getBundleInfo($this->getEntityTypeId());

    return array_keys($bundles);
  }

  protected function getStrategy(int $num): EntityGeneratorStrategyInterface {
    if ($num > $this->getSetting('batch_minimum_limit')) {
      return $this->withBatchStrategy;
    }

    return $this->withoutBatchStrategy;
  }

}
