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
 *   id = "layout_article_section",
 *   label = @Translation("Article"),
 *   category = @Translation("Content type specific"),
 *   path = "layouts/article_section",
 *   template = "layout--article-section",
 *   default_region = "content",
 *   icon_map = {
 *     {
 *       "content"
 *     }
 *   },
 *   regions = {
 *     "content" = {
 *       "label" = @Translation("Main content"),
 *     }
 *   }
 * )
 */
class ArticleLayout extends BaseLayout implements ContainerFactoryPluginInterface {

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * The context repository.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected ContextRepositoryInterface $contextRepository;

  /**
   * The article settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger, DateFormatterInterface $dateFormatter, ContextRepositoryInterface $contextRepository, ImmutableConfig $config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
    $this->dateFormatter = $dateFormatter;
    $this->contextRepository = $contextRepository;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    /** @var \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger */
    $logger = $container->get('logger.factory');
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter */
    $dateFormatter = $container->get('date.formatter');
    /** @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface $contextRepository */
    $contextRepository = $container->get('context.repository');
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $configFactory */
    $configFactory = $container->get('config.factory');
    $config = $configFactory->get('premium_articles_layout.settings');
    return new static($configuration, $plugin_id, $plugin_definition, $logger, $dateFormatter, $contextRepository, $config);
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);

    $build['#cache']['tags'] = $this->getCacheTags();

    $build['#attributes']['class'] = [
      'layout',
      $this->getPluginDefinition()->getTemplate()
    ];

    $entity = $this->getNode();
    if ($entity instanceof ContentEntityInterface) {
      if ($this->config->get('show_title') ?? TRUE) {
        $build['title'] = [
          '#markup' => $entity->label(),
        ];
      }
      if ($entity->hasField('field_description') && $this->config->get('show_description') ?? TRUE) {
        $build['description'] = [
          '#markup' => $entity->get('field_description')->getString(),
        ];
      }
      if ($entity->hasField('field_list_date') && $this->config->get('show_list_date') ?? TRUE) {
        $time = new DrupalDateTime();
        $build['list_date'] = [
          '#markup' => $this->dateFormatter->format($time->getTimestamp(), $this->config->get('list_date_format') ?? 'long'),
        ];
      }
      if ($entity->hasField('field_article_type') && $this->config->get('show_article_type') ?? TRUE) {
        /** @var EntityReferenceFieldItemList $field */
        $field = $entity->get('field_article_type');
        /** @var Term $term */
        foreach ($field->referencedEntities() as $term) {
          $render = $term->toLink()->toRenderable();
          $build['article_type'] = $render;
        }
      }
      if ($entity->hasField('field_article_categories') && $this->config->get('show_article_categories') ?? TRUE) {
        $build['article_categories'] = [];
        /** @var EntityReferenceFieldItemList $field */
        $field = $entity->get('field_article_categories');
        /** @var Term $term */
        foreach ($field->referencedEntities() as $term) {
          $render = $term->toLink()->toRenderable();
          $build['article_categories'][] = $render;
        }
      }
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['column_widths']['#access'] = FALSE;
    $form['column_width']['#access'] = FALSE;
    $form['column_spacing_top']['#access'] = FALSE;
    $form['column_spacing_bottom']['#access'] = FALSE;

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    $configuration['hide_breadcrumbs'] = FALSE;
    $configuration['column_width'] = 'section--width-full';
    $configuration['column_spacing_top'] = 'section--spacing-top-none';
    $configuration['column_spacing_bottom'] = 'section--spacing-bottom-none';
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return parent::getCacheTags() + $this->config->getCacheTags();
  }

  protected function getNode(): ?ContentEntityInterface {
    // fetch the available contexts
    $available_contexts = $this->contextRepository->getAvailableContexts();

    // ensure that the contexts have data by getting corresponding runtime contexts
    $available_runtime_contexts = $this->contextRepository->getRuntimeContexts(array_keys($available_contexts));

    $plugin_context_definition = new EntityContextDefinition('entity:node', t('Node'), FALSE);
    $matches = $this->contextHandler()
      ->getMatchingContexts($available_runtime_contexts, $plugin_context_definition);
    $matching_context = reset($matches);
    return $matching_context->getContextValue();
  }
}
