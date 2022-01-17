<?php
namespace Drupal\premium_articles\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'article_filter_form' formatter.
 *
 * @FieldFormatter(
 *   id = "article_filter_form",
 *   label = @Translation("Article filter form"),
 *   field_types = {
 *     "article_filter"
 *   }
 * )
 */
class ArticleFormFormatter extends ArticleListFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $options = $item->getValue();
      $options['entity_bundle'] = $items->getSetting('entity_bundle');
      $options['view_mode'] = $this->getSetting('view_mode');
      $options['show_total'] = $this->getSetting('show_total');
      $elements[$delta] = \Drupal::formBuilder()->getForm('Drupal\premium_articles\Form\ArticleFilterForm', $options);
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'show_total' => '',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
        'show_total' => [
          '#type' => 'select',
          '#title' => t('Display of total number of elements'),
          '#options' => $this->articleManager->getShowTotalOptions(),
          '#default_value' => $this->getSetting('show_total'),
          '#required' => FALSE,
        ],

        // Implement settings form.
      ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    if (!empty($this->getSetting('show_total'))) {
      $options = $this->articleManager->getShowTotalOptions();
      $summary[] = $this->t('Total display: @show_total', [
        '@show_total' => $options[$this->getSetting('show_total')]
      ]);
    }

    return $summary;
  }

}
