<?php

/**
 * @file
 * Provides common batch functions for every DevelGeneratePlugin.
 */

use Drupal\devel_generate_custom_entities\Strategy\EntityGeneratorWithBatchStrategy;

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
  /** @var \Drupal\devel_generate_custom_entities\Strategy\EntityGeneratorWithBatchStrategy $service */
  $service = \Drupal::service('devel_generate_custom_entities.strategy_with_batch');

  $service->printBatchFinishedMessage($success, $results['currentNumber']);
}
