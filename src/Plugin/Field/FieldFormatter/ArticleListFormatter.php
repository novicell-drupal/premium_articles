<?php

namespace Drupal\premium_articles\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_overview\Plugin\Field\FieldFormatter\OverviewListFormatter;
use Drupal\premium_articles\ArticleManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'article_list' formatter.
 *
 * @FieldFormatter(
 *   id = "article_list",
 *   label = @Translation("Article list"),
 *   field_types = {
 *     "article_filter",
 *     "overview_filter"
 *   }
 * )
 */
class ArticleListFormatter extends OverviewListFormatter {
}
