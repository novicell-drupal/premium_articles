<?php

namespace Drupal\premium_articles\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\premium_articles\ArticleManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'article_view' formatter.
 *
 * @FieldFormatter(
 *   id = "article_list",
 *   label = @Translation("Article list"),
 *   field_types = {
 *     "article_filter"
 *   }
 * )
 */
class ArticleListFormatter extends FormatterBase {

  /**
   * @var \Drupal\premium_articles\ArticleManager
   */
  protected $articleManager;

  /**
   * Constructs a FormatterBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ArticleManager $articleManager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->articleManager = $articleManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['label'], $configuration['view_mode'], $configuration['third_party_settings'], $container->get('premium_articles.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $entity_bundle = $items->getSetting('entity_bundle');
      $nodes = $this->articleManager->getArticles($entity_bundle, $item->getValue());
      $elements[$delta] = \Drupal::entityTypeManager()->getViewBuilder('node')->viewMultiple($nodes, $this->getSetting('view_mode'));
      $elements[$delta]['#cache']['tags'][] = 'node_list';
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'view_mode' => 'teaser',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
        'view_mode' => [
          '#type' => 'select',
          '#title' => t('View mode'),
          '#options' => $this->articleManager->getViewModes(),
          '#default_value' => $this->getSetting('view_mode'),
          '#required' => TRUE,
        ],

        // Implement settings form.
      ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = t('View mode: @view_mode', [
      '@view_mode' => $this->getSetting('view_mode')
    ]);

    return $summary;
  }


}
