<?php
namespace Drupal\premium_articles;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Pager\Pager;
use Drupal\node\Entity\Node;

class ArticleManager {

  /**
   * @var EntityStorageInterface
   */
  protected $taxonomyStorage;

  function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->taxonomyStorage = $entityTypeManager->getStorage('taxonomy_term');
  }

  /**
   * @return array
   */
  public function getTypes() {
    static $types = [];
    if (empty($types)) {
      $query = $this->taxonomyStorage->getQuery();
      $query->condition('vid', "article_types");
      $tids = $query->execute();
      $terms = $this->taxonomyStorage->loadMultiple($tids);
      foreach ($terms as $term) {
        $types[$term->id()] = $term->label();
      }
    }
    return $types;
  }

  /**
   * @return array
   */
  public function getCategories() {
    static $categories = [];
    if (empty($categories)) {
      $query = $this->taxonomyStorage->getQuery();
      $query->condition('vid', "article_categories");
      $tids = $query->execute();
      $terms = $this->taxonomyStorage->loadMultiple($tids);
      foreach ($terms as $term) {
        $categories[$term->id()] = $term->label();
      }
    }
    return $categories;
  }

  public function getCountOptions() {
    return [
      5 => '5',
      10 => '10',
      15 => '15',
      20 => '20',
      25 => '25'
    ];
  }

  public function getSortCriterias() {
    return [
      'newest' => t('Newest first'),
      'oldest' => t('Oldest first'),
    ];
  }

  /**
   * @return array
   */
  public function getViewModes() {
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $repository */
    $repository = \Drupal::service('entity_display.repository');
    return $repository->getViewModeOptionsByBundle('node', 'article');
  }

  protected function getArticleQuery($filter, $page = 0) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('status', 1);
    if (count($filter['types']) > 0) {
      $query->condition('field_article_type', $filter['types'], 'IN');
    }
    if (count($filter['categories']) > 0) {
      $query->condition('field_article_categories', $filter['categories'], 'IN');
    }
    return $query;
  }

  /**
   * @param array $filter
   *
   * @return Pager
   */
  public function getArticleCount($filter) {
    $query = $this->getArticleQuery($filter);
    $query->count();
    $count = $query->execute();

    /** @var \Drupal\Core\Pager\PagerManagerInterface $pagerManager */
    $pagerManager = \Drupal::service('pager.manager');
    $pager = $pagerManager->createPager($count, $filter['count']);
    return $pager;
  }

  /**
   * @param array $filter
   * @param int $page
   *
   * @return Node[]
   */
  public function getArticles($filter, $page = 0) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('status', 1);
    if (count($filter['types']) > 0) {
      $query->condition('field_article_type', $filter['types'], 'IN');
    }
    if (count($filter['categories']) > 0) {
      $query->condition('field_article_categories', $filter['categories'], 'IN');
    }
    if ($filter['pagination']) {
      \Drupal::requestStack()->getCurrentRequest()->query->set('page', $page);
      $query->pager($filter['count']);
    } elseif (isset($filter['count'])) {
      $query->range($page * $filter['count'], $filter['count']);
    }
    switch ($filter['sort']) {
      case 'oldest':
        $query->sort('field_list_date', 'ASC');
        break;
      default:
        $query->sort('field_list_date', 'DESC');
        break;
    }

    $nids = $query->execute();

    return Node::loadMultiple($nids);
  }

}
