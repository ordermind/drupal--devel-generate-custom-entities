<?php

namespace Drupal\devel_generate_custom_entities\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\devel_generate\Commands\DevelGenerateCommands as BaseCommand;

class DevelGenerateCommand extends BaseCommand {

  /**
   * Create custom entities.
   *
   * @command devel-generate:entities
   * @aliases genent, devel-generate-entities
   *
   * @param string $entity_type
   * @param int $num
   *   Number of vocabularies to generate.
   * @param array $options
   *   Array of options as described below.
   *
   * @option delete-existing Delete all existing entities before generating new ones.
   */
  public function entities(string $entity_type, $num = 1, array $options = ['delete-existing' => FALSE]) {

    $this->generate();
  }

  /**
   * The standard drush validate hook.
   *
   * @hook validate
   *
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *   The data sent from the drush command.
   */
  public function validate(CommandData $commandData) {
    $manager = $this->getManager();
    $args = $commandData->input()->getArguments();
    // The command name is the first argument but we do not need this.
    array_shift($args);

    /** @var DevelGenerateBaseInterface $instance */
    $instance = $manager->createInstance($args['entity_type'], []);
    $this->setPluginInstance($instance);
    $parameters = $instance->validateDrushParams($args, $commandData->input()->getOptions());
    $this->setParameters($parameters);
  }

}
