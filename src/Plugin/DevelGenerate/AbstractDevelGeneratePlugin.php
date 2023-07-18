<?php

declare(strict_types=1);

namespace Drupal\devel_generate_custom_entities\Plugin\DevelGenerate;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\devel_generate\DevelGenerateBase;
use Drupal\devel_generate_custom_entities\Strategy\EntityGeneratorDrushStrategy;
use Drupal\devel_generate_custom_entities\Strategy\EntityGeneratorStrategyInterface;
use Drupal\devel_generate_custom_entities\Strategy\EntityGeneratorWebBatchStrategy;
use Drupal\devel_generate_custom_entities\Strategy\EntityGeneratorWebStrategy;
use Drupal\devel_generate_custom_entities\ValueObject\EntityGenerationOptions;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractDevelGeneratePlugin extends DevelGenerateBase implements ContainerFactoryPluginInterface {
  protected EntityGeneratorWebBatchStrategy $webBatchStrategy;
  protected EntityGeneratorWebStrategy $webStrategy;
  protected EntityGeneratorDrushStrategy $drushStrategy;
  protected AccountProxyInterface $currentUser;
  protected EntityTypeBundleInfoInterface $bundleInfo;

  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    EntityGeneratorWebBatchStrategy $webBatchStrategy,
    EntityGeneratorWebStrategy $webStrategy,
    EntityGeneratorDrushStrategy $drushStrategy,
    AccountProxyInterface $currentUser,
    EntityTypeBundleInfoInterface $bundleInfo
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->webBatchStrategy = $webBatchStrategy;
    $this->webStrategy = $webStrategy;
    $this->drushStrategy = $drushStrategy;
    $this->currentUser = $currentUser;
    $this->bundleInfo = $bundleInfo;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('devel_generate_custom_entities.strategy_web_batch'),
      $container->get('devel_generate_custom_entities.strategy_web'),
      $container->get('devel_generate_custom_entities.strategy_drush'),
      $container->get('current_user'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form['entity_type'] = [
      '#type' => 'hidden',
      '#value' => $this->getEntityTypeId(),
    ];

    $form['bundles'] = [
      '#type' => 'select2',
      '#title' => $this->t('For which bundles should entities be generated?'),
      '#multiple' => TRUE,
      '#options' => $this->getBundleOptions($this->getEntityTypeId()),
      '#empty_option' => $this->t('- All -'),
    ];

    $form['num'] = [
      '#type' => 'textfield',
      '#title' => $this->t('How many entities would you like to generate?'),
      '#default_value' => $this->getSetting('num') ?? 100,
      '#size' => 10,
    ];

    $form['delete_existing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete all existing entities before generating new ones.'),
      '#default_value' => $this->getSetting('delete_existing') ?? TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function generate(array $values): void {
    $num = (int) $values['num'];
    $entityTypeId = $values['entity_type'];
    $bundles = array_values($values['bundles']) ?: array_keys($this->getBundleOptions($entityTypeId));
    $deleteExisting = (bool) $values['delete_existing'];
    $drush = !empty($values['drush']);

    $generationOptions = new EntityGenerationOptions(
      $drush,
      $entityTypeId,
      $this->getLabelPattern($entityTypeId),
      $bundles,
      $num,
      $deleteExisting,
      (int) $this->currentUser->id() ?: 1
    );

    $strategy = $this->getStrategy($generationOptions);

    $strategy->generateEntities($generationOptions);
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrushParams(array $args, array $options = []): array {
    $entityTypeId = $args['entity_type'];
    $entityTypes = $this->getEntityTypeManager()->getDefinitions();
    if (!isset($entityTypes[$entityTypeId])) {
      throw new \InvalidArgumentException('Error generating entities: The entity type "' . $entityTypeId . '" does not exist!');
    }

    $bundles = str_replace(' ', '', $options['bundles']) ?? '';
    $bundles = array_filter(explode(',', $bundles));

    $invalidBundles = array_diff($bundles, array_keys($this->getBundleOptions($entityTypeId)));
    if ($invalidBundles) {
      throw new \InvalidArgumentException('Error generating entities: The bundle "' . reset($invalidBundles) . '" does not exist!');
    }

    $values = [
      'entity_type' => $entityTypeId,
      'bundles' => $bundles,
      'num' => $args['num'],
      'delete_existing' => $options['kill'],
      'drush' => TRUE,
    ];

    return $values;
  }

  protected function getEntityTypeId(): string {
    return $this->getPluginId();
  }

  /**
   * @return string The label pattern as described in the EntityGenerationOptions.
   *
   * @see EntityGenerationOptions
   */
  protected function getLabelPattern(string $entityTypeId): string {
    $entityType = $this->getEntityTypeManager()->getDefinition($entityTypeId);

    return $entityType->getLabel() . ' #@num';
  }

  /**
   * @return array<string, string>
   */
  protected function getBundleOptions(string $entityTypeId): array {
    $bundles = $this->bundleInfo->getBundleInfo($entityTypeId);

    return array_combine(array_keys($bundles), array_map(fn (array $bundleInfo) => $bundleInfo['label'], $bundles));
  }

  protected function getStrategy(EntityGenerationOptions $options): EntityGeneratorStrategyInterface {
    if ($options->isDrush()) {
      return $this->drushStrategy;
    }

    if ($options->getNumberOfEntities() >= $this->getBatchMinimumLimit()) {
      return $this->webBatchStrategy;
    }

    return $this->webStrategy;
  }

  /**
   * @return int The minimum number of generated entities to use batch processing for the web interface.
   */
  protected function getBatchMinimumLimit(): int {
    return $this->getSetting('batch_minimum_limit') ?? 50;
  }

}
