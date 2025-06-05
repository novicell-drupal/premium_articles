<?php
namespace Drupal\premium_articles\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Plugin implementation of the Article Filter field type.
 *
 * @FieldType(
 *   id = "article_filter",
 *   module = "premium_articles",
 *   label = @Translation("Article filter"),
 *   description = @Translation("Field with filter and display options for articles."),
 *   category = "entity_overviews",
 *   default_widget = "article_filter_widget",
 *   default_formatter = "article_list"
 * )
 */
class ArticleFilterType extends FieldItemBase {

  /**
   * @var \Drupal\entity_overview\OverviewManager
   */
  protected $overviewManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    $this->overviewManager = \Drupal::service('entity_overview.manager');
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
        'entity_bundle' => 'node.article',
        'allow_facets' => FALSE,
      ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element['entity_bundle'] = [
      '#type' => 'select',
      '#title' => t('Article entity bundle'),
      '#options' => $this->overviewManager->getOverviewConfigs(),
      '#default_value' => $this->getSetting('entity_bundle'),
    ];

    $element['allow_facets'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow exposing facets'),
      '#default_value' => $this->getSetting('allow_facets'),
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
    $properties['fields'] = MapDataDefinition::create()
      ->setLabel(t('Fields'))
      ->setRequired(TRUE);
    $properties['count'] = DataDefinition::create('integer')
      ->setLabel(t('Default result count'))
      ->setRequired(TRUE);
    $properties['sort'] = DataDefinition::create('string')
      ->setLabel(t('Sort criteria'))
      ->setRequired(TRUE);
    $properties['pagination'] = DataDefinition::create('boolean')
      ->setLabel(t('Pagination'))
      ->setRequired(TRUE);
    $properties['facets'] = ListDataDefinition::create('list')
      ->setLabel(t('Filters'))
      ->setItemDefinition(DataReferenceTargetDefinition::create('string')
        ->setLabel(new TranslatableMarkup('Field')))
      ->setRequired(FALSE);

    return $properties;
  }

  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'fields' => [
          'description' => 'Serialized array of default values of fields.',
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ],
        'count' => [
          'type' => 'int',
        ],
        'sort' => [
          'type' => 'varchar',
          'length' => 32,
        ],
        'pagination' => [
          'type' => 'int',
          'size' => 'tiny',
        ],
        'facets' => [
          'type' => 'blob',
          'serialize' => TRUE,
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

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    if (!empty($this->values['element_id_field'])) {
      $this->values['fields'][$this->values['element_id_field']] = [$this->getEntity()->id()];
      unset($this->values['element_id_field']);
      return TRUE;
    }
    return FALSE;
  }

}
