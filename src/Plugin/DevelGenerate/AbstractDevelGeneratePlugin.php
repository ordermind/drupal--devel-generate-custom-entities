<?php

declare(strict_types=1);

namespace Drupal\devel_generate_custom_entities\Plugin\DevelGenerate;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\devel_generate\DevelGenerateBase;
use Drupal\devel_generate_custom_entities\Strategy\EntityGeneratorStrategySelector;
use Drupal\devel_generate_custom_entities\ValueObject\EntityGenerationOptions;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractDevelGeneratePlugin extends DevelGenerateBase implements ContainerFactoryPluginInterface {
  protected const DEFAULT_NUM = 100;
  protected const DEFAULT_DELETE_EXISTING = TRUE;
  protected const DEFAULT_BATCH_MINIMUM_LIMIT = 50;

  protected EntityGeneratorStrategySelector $strategySelector;
  protected AccountProxyInterface $currentUser;
  protected EntityTypeBundleInfoInterface $bundleInfo;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $plugin = new static($configuration, $plugin_id, $plugin_definition);

    $plugin->setStrategySelector($container->get('devel_generate_custom_entities.strategy_selector'));
    $plugin->setCurrentUser($container->get('current_user'));
    $plugin->setBundleInfo($container->get('entity_type.bundle.info'));

    return $plugin;
  }

  private function setStrategySelector(EntityGeneratorStrategySelector $strategySelector) {
    $this->strategySelector = $strategySelector;
  }

  private function setCurrentUser(AccountProxyInterface $currentUser) {
    $this->currentUser = $currentUser;
  }

  private function setBundleInfo(EntityTypeBundleInfoInterface $bundleInfo) {
    $this->bundleInfo = $bundleInfo;
  }

  protected function getEntityTypeManager(): EntityTypeManagerInterface {
    return $this->entityTypeManager;
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
      '#default_value' => $this->getSetting('num') ?? static::DEFAULT_NUM,
      '#size' => 10,
    ];

    $form['delete_existing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete all existing entities before generating new ones.'),
      '#default_value' => $this->getSetting('delete_existing') ?? static::DEFAULT_DELETE_EXISTING,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function generate(array $values): void {
    $num = (int) ($values['num'] ?? static::DEFAULT_NUM);
    $entityTypeId = (string) $values['entity_type'];
    $bundles = array_values((array) ($values['bundles'] ?? [])) ?: array_keys($this->getBundleOptions($entityTypeId));
    $deleteExisting = (bool) ($values['delete_existing'] ?? static::DEFAULT_DELETE_EXISTING);
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

    $strategy = $this->strategySelector->selectStrategy($generationOptions, $this->getBatchMinimumLimit());

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

  /**
   * @return int The minimum number of generated entities to use batch processing for the web interface.
   */
  protected function getBatchMinimumLimit(): int {
    return $this->getSetting('batch_minimum_limit') ?? static::DEFAULT_BATCH_MINIMUM_LIMIT;
  }

}
