<?php

declare(strict_types=1);

namespace Drupal\devel_generate_custom_entities\Plugin\DevelGenerate;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a DevelGenerate plugin.
 *
 * @DevelGenerate(
 *   id = "default_custom_entity",
 *   label = "custom entities",
 *   description = "Generate custom entities",
 *   url = "default_custom_entity",
 *   permission = "administer devel_generate"
 * )
 */
class DefaultCustomEntityDevelGenerate extends AbstractDevelGeneratePlugin {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);

    $this->getEntityTypeOptions();

    $form['#prefix'] = '<div id="ajax-wrapper">';
    $form['#suffix'] = '</div>';

    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('For which entity type should entities be generated?'),
      '#options' => $this->getEntityTypeOptions(),
      '#required' => TRUE,
      '#weight' => -10,
      '#ajax' => [
        'callback' => [$this, 'ajaxCallback'],
        'wrapper' => 'ajax-wrapper',
        'method' => 'replace',
        'effect' => 'fade',
      ],
    ];

    if (!empty($form_state->getValue('entity_type'))) {
      $form['bundles']['#access'] = TRUE;
      $form['bundles']['#options'] = $this->getBundleOptions($form_state->getValue('entity_type'));
    }
    else {
      $form['bundles']['#access'] = FALSE;
      $form['bundles']['#options'] = [];
    }

    return $form;
  }

  protected function getEntityTypeOptions(): array {
    $excludedEntityTypes = array_flip([
      'file',
      'menu_link_content',
      'node',
      'path_alias',
      'taxonomy_term',
      'user',
    ]);

    $entityTypes = array_filter(
      $this->getEntityTypeManager()->getDefinitions(),
      fn (EntityTypeInterface $entityType, string $name) => $entityType instanceof ContentEntityTypeInterface && !isset($excludedEntityTypes[$name]),
      ARRAY_FILTER_USE_BOTH
    );

    return array_combine(
      array_keys($entityTypes),
      array_map(fn (EntityTypeInterface $entityType) => $entityType->getLabel(), $entityTypes)
    );
  }

  public function ajaxCallback(array $form, FormStateInterface $form_state): array {
    return $form;
  }

}
