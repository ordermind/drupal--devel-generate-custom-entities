Instructions for usage:

Create a plugin for DevelGenerate that extends Drupal\devel_generate_custom_entities\Plugin\DevelGenerate\AbstractDevelGeneratePlugin. You will need to define the plugin in your own class with annotations. An important thing here is to use the machine name of the custom entity type that you want to generate entities for as the id of the plugin. Apart from that, just implement the required methods and then you're good to go!
