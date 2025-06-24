<?php
namespace Drupal\premium_articles_layout\Plugin\Layout;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Annotation\ContextDefinition;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\Router;
use Drupal\premium_core\Plugin\Layout\BaseLayout;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A layout for articles.
 *
 * @Layout(
 *   id = "layout_article_sidebar_section",
 *   label = @Translation("Article with sidebar"),
 *   category = @Translation("Content type specific"),
 *   path = "layouts/article_section",
 *   template = "layout--article-sidebar-section",
 *   default_region = "content",
 *   icon_map = {
 *     {
 *       "content",
 *       "sidebar"
 *     }
 *   },
 *   regions = {
 *     "content" = {
 *       "label" = @Translation("Main content"),
 *     },
 *     "sidebar" = {
 *       "label" = @Translation("Sidebar"),
 *     }
 *   }
 * )
 */
class ArticleSidebarLayout extends ArticleLayout {}
