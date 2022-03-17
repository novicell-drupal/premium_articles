<?php
namespace Drupal\premium_articles\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_overview\Plugin\Field\FieldFormatter\OverviewFormFormatter;

/**
 * Plugin implementation of the 'article_filter_form' formatter.
 *
 * @FieldFormatter(
 *   id = "article_filter_form",
 *   label = @Translation("Article filter form"),
 *   field_types = {
 *     "article_filter",
 *     "overview_filter"
 *   }
 * )
 */
class ArticleFormFormatter extends OverviewFormFormatter {

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
}
