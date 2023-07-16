<?php

/**
 * @file
 * Provides common batch functions for every DevelGeneratePlugin.
 */

use Drupal\devel_generate_custom_entities\Strategy\EntityGeneratorWebBatchStrategy;

/**
 * Calls the correct method responsible for handling a given batch operation.
 */
function devel_generate_custom_entities_operation(EntityGeneratorWebBatchStrategy $object, $method, $vars, &$context) {
  return $object->$method($vars, $context);
}

/**
 * Standard finish batch function.
 */
function devel_generate_custom_entities_batch_finished(bool $success, array $results, array $operations) {
  /** @var \Drupal\devel_generate_custom_entities\Strategy\EntityGeneratorWebBatchStrategy $service */
  $service = \Drupal::service('devel_generate_custom_entities.strategy_web_batch');

  $service->printBatchFinishedMessage($success, $results['currentNumber']);
}
