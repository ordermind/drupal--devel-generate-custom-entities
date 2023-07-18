Instructions for usage:

For simple entity types, you can use the default implementation either by visiting 
/admin/config/development/generate/default-custom-entity or by running `drush genent` and entering your entity type.

For more complex entity types, you can create a plugin for DevelGenerate that extends 
Drupal\devel_generate_custom_entities\Plugin\DevelGenerate\AbstractDevelGeneratePlugin. You will need to define the 
plugin in your own class with annotations. An important thing here is to use the machine name of the custom entity type 
that you want to generate entities for as the id of the plugin. Then you can simply override the methods that you need.
For an example, see Drupal\devel_generate_custom_entities\Plugin\DevelGenerate\DefaultCustomEntityDevelGenerate.
