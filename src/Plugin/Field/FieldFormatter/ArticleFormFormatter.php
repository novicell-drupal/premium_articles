<?php
namespace Drupal\premium_articles\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

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
      $options['view_mode'] = $this->getSetting('view_mode');
      $elements[$delta] = \Drupal::formBuilder()->getForm('Drupal\premium_articles\Form\ArticleFilterForm', $options);
    }

    return $elements;
  }

}
