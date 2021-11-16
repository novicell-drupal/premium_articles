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
   * {@inheritdoc}
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
    $entity_bundle = $this->getFieldSetting('entity_bundle');
    $fields = $this->articleManager->getFieldFormElements($entity_bundle);

    if (!empty($item) && $item->getEntity()->getEntityTypeId() == 'taxonomy_term') {
      $vid = $item->getEntity()->bundle();
      foreach ($fields as $field_name => $form_element) {
        if ($form_element['vid'] == $vid) {
          $element[$field_name . '_title'] = [
            '#type' => 'item',
            '#title' => $form_element['label'],
            '#description' => $item->getEntity()->label() ?? $this->t('Show articles of this type.'),
          ];
          $element['fields'][$field_name] = [
            '#type' => 'hidden',
            '#default_value' => $item->getEntity()->id()
          ];
          unset($fields[$field_name]);
        }
      }
    }

    foreach ($fields as $field_name => $form_element) {
      $element['fields'][$field_name] = [
        '#type' => $form_element['form_element'],
        '#title' => $form_element['label'],
        '#description' => $this->t('If none are selected, all are allowed.'),
        '#options' => $form_element['options'],
        '#default_value' => $item->fields[$field_name] ?? []
      ];
    }

    $element['count'] = [
      '#type' => 'number',
      '#title' => $this->t('Count'),
      '#description' => $this->t('How many articles to show at once.'),
      '#default_value' => $item->count ?? 5,
    ];

    $element['sort'] = [
      '#type' => 'select',
      '#title' => $this->t('Sort criteria'),
      '#description' => $this->t('What criteria to sort articles by.'),
      '#options' => $this->articleManager->getSortCriterias(),
      '#default_value' => $item->sort ?? 'newest',
    ];

    if ($this->getFieldSetting('allow_facets')) {
      $filter_options = [];
      foreach ($fields as $field_name => $form_element) {
        $filter_options[$field_name] = $form_element['label'];
      }
      $filter_options['count'] = $this->t('Page size select');
      $filter_options['sort'] = $this->t('Sort select');
      if (!empty($filter_options)) {
        $element['facets'] = [
          '#type' => 'checkboxes',
          '#title' => $this->t('Facets'),
          '#description' => $this->t('Select the facets that you want to expose to the user.'),
          '#options' => $filter_options,
          '#default_value' => $item->facets ?? [],
        ];
      }

      $element['pagination'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Pagination'),
        '#description' => $this->t('Display pager at the bottom.'),
        '#default_value' => $item->pagination ?? FALSE,
      ];
    } else {
      $element['facets'] = [
        '#type' => 'hidden',
        '#default_value' => '',
      ];
      $element['pagination'] = [
        '#type' => 'hidden',
        '#default_value' => FALSE,
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
    if (empty($values['facets'])) {
      $values['facets'] = [];
    }
    if (is_string($values['facets'])) {
      $values['facets'] = [$values['facets']];
    } elseif (is_array($values['facets'])) {
      $result = [];
      foreach ($values['facets'] as $value) {
        if (!empty($value)) {
          $result[] = $value;
        }
      }
      $values['facets'] = $result;
    }
    if (is_string($values['count'])) {
      $values['count'] = intval($values['count']);
    }
    foreach ($values['fields'] as $field_name => $selections) {
      if (is_array($selections)) {
        $result = [];
        foreach ($selections as $key => $value) {
          if (!empty($value)) {
            $result[] = $value;
          }
        }
        $values['fields'][$field_name] = $result;
      }
    }
    $values['pagination'] = boolval($values['pagination']);

    return $values;
  }
}
