<?php

namespace Drupal\premium_articles\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Checkboxes;
use Drupal\premium_articles\ArticleManager;
use Drupal\styles\StylesManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'article_filter' widget.
 *
 * @FieldWidget(
 *   id = "article_filter_widget",
 *   label = @Translation("Article filter"),
 *   description = @Translation("Select options for filtering."),
 *   field_types = {
 *     "article_filter"
 *   },
 *   multiple_values = TRUE
 * )
 */
class ArticleFilterWidget extends WidgetBase {

  /**
   * @var \Drupal\premium_articles\ArticleManager
   */
  protected $articleManager;

  /**
   * Constructs a WidgetBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ArticleManager $articleManager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->articleManager = $articleManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['third_party_settings'], $container->get('premium_articles.manager'));
  }

  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var FieldItemInterface $item */
    $item = $items[$delta] ?? [];

    if (!empty($item) && $item->getEntity()->getEntityTypeId() == 'taxonomy_term') {
      $element['types'] = [
        '#type' => 'hidden',
        '#default_value' => [$item->getEntity()->id()]
      ];
      $element['types_title'] = [
        '#type' => 'item',
        '#title' => $this->t('Types'),
        '#description' => $item->getEntity()->label() ?? $this->t('Show articles of this type.'),
      ];
    } else {
      $element['types'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Types'),
        '#description' => $this->t('What article types to display. Choose none to display all.'),
        '#options' => $this->articleManager->getTypes(),
        '#default_value' => $item->types ?? []
      ];
    }

    //dpm($item->getValue() ?? []);
    $element['categories'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Categories'),
      '#description' => $this->t('What article categories to display. Choose none to display all.'),
      '#options' => $this->articleManager->getCategories(),
      '#default_value' => $item->categories ?? []
    ];

    $element['count'] = [
      '#type' => 'number',
      '#title' => $this->t('Count'),
      '#description' => $this->t('How many articles to show at once.'),
      '#default_value' => $item->count ?? 5,
    ];

    if ($this->getFieldSetting('allow_filters')) {
      $element['pagination'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Pagination'),
        '#description' => $this->t('Display pager at the bottom.'),
        '#default_value' => $item->pagination ?? FALSE,
      ];

      if (!empty($item) && $item->getEntity()->getEntityTypeId() == 'taxonomy_term') {
        $element['type_filter'] = [
          '#type' => 'hidden',
          '#default_value' => ''
        ];
      } else {
        $element['type_filter'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Type filter'),
          '#description' => $this->t('Allow users to filter by article type.'),
          '#default_value' => !empty($item->type_filter),
        ];
      }

      $element['category_filter'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Category filter'),
        '#description' => $this->t('Allow users to filter by category.'),
        '#default_value' => !empty($item->category_filter),
      ];

      $element['count_filter'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Page size select'),
        '#description' => $this->t('Allow users to select articles per page.'),
        '#default_value' => !empty($item->count_filter),
      ];
    } else {
      $element['pagination'] = [
        '#type' => 'hidden',
        '#default_value' => FALSE,
      ];
      $element['type_filter'] = [
        '#type' => 'hidden',
        '#default_value' => '',
      ];
      $element['category_filter'] = [
        '#type' => 'hidden',
        '#default_value' => '',
      ];
      $element['count_filter'] = [
        '#type' => 'hidden',
        '#default_value' => '',
      ];
    }

    // If cardinality is 1, ensure a proper label is output for the field.
    if ($this->fieldDefinition->getFieldStorageDefinition()->getCardinality() == 1) {
      $element += [
        '#type' => 'fieldset',
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    if (is_string($values['types'])) {
      $values['types'] = [intval($values['types'])];
    } else {
      $types = [];
      foreach ($values['types'] as $type) {
        if ($type) {
          $types[] = intval($type);
        }
      }
      $values['types'] = $types;
    }
    $categories = [];
    foreach ($values['categories'] as $category) {
      if ($category) {
        $categories[] = intval($category);
      }
    }
    $values['categories'] = $categories;
    if (is_string($values['count'])) {
      $values['count'] = intval($values['count']);
    }
    $values['pagination'] = boolval($values['pagination']);
    $values['type_filter'] = empty($values['type_filter']) ? '' : 'checkboxes';
    $values['category_filter'] = empty($values['category_filter']) ? '' : 'radios';
    $values['count_filter'] = empty($values['count_filter']) ? '' : 'select';

    return $values;
  }
}
