<?php

/**
 * @file
 * Provides common batch functions for every DevelGeneratePlugin.
 */

use Drupal\devel_generate_custom_entities\EntityGeneration\EntityGeneratorWithBatchStrategy;

/**
 * Calls the correct method responsible for handling a given batch operation.
 */
function devel_generate_custom_entities_operation(EntityGeneratorWithBatchStrategy $object, $method, $vars, &$context) {
  return $object->$method($vars, $context);
}

/**
 * Standard finish batch function.
 */
function devel_generate_custom_entities_batch_finished(bool $success, array $results, array $operations) {
  /** @var \Drupal\devel_generate_custom_entities\EntityGeneration\EntityGeneratorWithBatchStrategy $service */
  $service = \Drupal::service('devel_generate_custom_entities_entity_generator_batch_strategy');

  $service->printBatchFinishedMessage($success, $results['currentNumber']);
}
