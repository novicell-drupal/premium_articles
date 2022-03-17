<?php

namespace Drupal\premium_articles\Plugin\Field\FieldWidget;

use Drupal\entity_overview\Plugin\Field\FieldWidget\OverviewFilterWidget;

/**
 * Plugin implementation of the 'article_filter' widget.
 *
 * @FieldWidget(
 *   id = "article_filter_widget",
 *   label = @Translation("Article filter"),
 *   description = @Translation("Select options for filtering."),
 *   field_types = {
 *     "article_filter",
 *     "overview_filter"
 *   },
 *   multiple_values = TRUE
 * )
 */
class ArticleFilterWidget extends OverviewFilterWidget {
}
