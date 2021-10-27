<?php
namespace Drupal\premium_articles\Plugin\Field\FieldType;

use Drupal;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\options\Plugin\Field\FieldType\ListItemBase;
use Drupal\options\Plugin\Field\FieldType\ListStringItem;
use Drupal\styles\StylesManager;
use Drupal\user\UserInterface;

/**
 * Plugin implementation of the Article Filter field type.
 *
 * @FieldType(
 *   id = "article_filter",
 *   module = "premium_articles",
 *   label = @Translation("Article filter"),
 *   description = @Translation("Field with filter and display options for articles."),
 *   category = @Translation("Articles"),
 *   default_widget = "article_filter_widget",
 *   default_formatter = "article_list"
 * )
 */
class ArticleFilterType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
        'allow_filters' => FALSE,
      ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $setting = $this->getSetting('allow_filters');

    $element['allow_filters'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow exposing filters'),
      '#default_value' => $setting,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function storageSettingsToConfigData(array $settings) {
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function storageSettingsFromConfigData(array $settings) {
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    // A filter item has no main property.
    return NULL;
  }

  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['types'] = ListDataDefinition::create('list')
      ->setLabel(t('Types'))
      ->setItemDefinition(DataReferenceTargetDefinition::create('integer')
        ->setLabel(new TranslatableMarkup('@label ID', ['@label' => 'Taxonomy term']))
        ->setSetting('unsigned', TRUE))
      ->setRequired(FALSE);
    $properties['categories'] = ListDataDefinition::create('list')
      ->setLabel(t('Categories'))
      ->setItemDefinition(DataReferenceTargetDefinition::create('integer')
        ->setLabel(new TranslatableMarkup('@label ID', ['@label' => 'Taxonomy term']))
        ->setSetting('unsigned', TRUE))
      ->setRequired(FALSE);
    $properties['count'] = DataDefinition::create('integer')
      ->setLabel(t('Default result count'))
      ->setRequired(TRUE);
    $properties['pagination'] = DataDefinition::create('boolean')
      ->setLabel(t('Use pager'))
      ->setRequired(TRUE);
    $properties['type_filter'] = DataDefinition::create('string')
      ->setLabel(t('Type filter'))
      ->setRequired(TRUE);
    $properties['category_filter'] = DataDefinition::create('string')
      ->setLabel(t('Category filter'))
      ->setRequired(TRUE);
    $properties['count_filter'] = DataDefinition::create('string')
      ->setLabel(t('Count filter'))
      ->setRequired(TRUE);

    return $properties;
  }

  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'types' => [
          'type' => 'blob',
          'serialize' => TRUE,
        ],
        'categories' => [
          'type' => 'blob',
          'serialize' => TRUE,
        ],
        'count' => [
          'type' => 'int',
        ],
        'pagination' => [
          'type' => 'int',
          'size' => 'tiny',
        ],
        'type_filter' => [
          'type' => 'varchar',
          'length' => 32,
        ],
        'category_filter' => [
          'type' => 'varchar',
          'length' => 32,
        ],
        'count_filter' => [
          'type' => 'varchar',
          'length' => 32,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return FALSE;
  }

}
