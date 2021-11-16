<?php
namespace Drupal\premium_articles;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Pager\Pager;
use Drupal\node\Entity\Node;
use JetBrains\PhpStorm\Pure;

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
  public function getEntityBundles() {
    // TODO: Rewrite to use actial configs
    return [
      'node.article' => t('Article')
    ];
  }

  public function getEntityBundleConfig($entity_bundle) {
    // TODO: Rewrite to use actial configs
    return [
      'id' => $entity_bundle,
      'entity_type_id' => 'node',
      'bundle' => 'article',
      'fields' => [
        'field_article_type' => 'checkboxes',
        'field_article_categories' => 'checkboxes'
      ],
      'sort_field' => 'field_list_date'
    ];
  }

  /**
   * @param $entity_bundle
   * @return array
   */
  public function getFieldFormElements($entity_bundle) {
    // TODO: Get more information from field definitions and cache it
    $fields = $this->getEntityBundleConfig($entity_bundle)['fields'];
    $elements = [];
    foreach ($fields as $field => $form_element) {
      $elements[$field] = $this->getFieldFormElement($entity_bundle, $field, $form_element);
    }
    return $elements;
  }

  public function getFieldFormElement($entity_bundle, $field_name, $element_type) {
    $element = [
      'form_element' => $element_type,
    ];

    $entity_info = explode('.', $entity_bundle);
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager */
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $definitions = $entityFieldManager->getFieldDefinitions($entity_info[0], $entity_info[1]);
    $definition = $definitions[$field_name];
    switch ($definition->getType()) {
      case 'entity_reference':
        $settings = $definition->getSettings() ?? [];
        if ($settings['target_type'] == 'taxonomy_term') {
          if (count($settings['handler_settings']['target_bundles']) == 1) {
            $element['label'] = $definition->getLabel();
            $element['source'] = 'taxonomy_term';
            $element['options'] = [];
            $vid = reset($settings['handler_settings']['target_bundles']);
            $element['vid'] = $vid;
            $query = $this->taxonomyStorage->getQuery();
            $query->condition('vid', $vid)
              ->sort($settings['handler_settings']['sort']['field'], $settings['handler_settings']['sort']['direction']);
            $tids = $query->execute();
            $terms = $this->taxonomyStorage->loadMultiple($tids);
            foreach ($terms as $term) {
              $element['options'][$term->id()] = $term->label();
            }
          } else {
            \Drupal::logger('premium_articles')->error('Field %field is not supported by Premium Articles', ['%field' => $field_name]);
            return [];
          }
        } else {
          \Drupal::logger('premium_articles')->error('Field %field is not supported by Premium Articles', ['%field' => $field_name]);
          return [];
        }
        break;
      default:
        \Drupal::logger('premium_articles')->error('Field %field is not supported by Premium Articles', ['%field' => $field_name]);
        return [];
    }
    return $element;
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

  /**
   * @param array $filter
   * @param int $page
   *
   * @return Node[]
   */
  public function getArticles($entity_bundle, $filter = [], $page = 0) {
    $entity_info = explode('.', $entity_bundle);
    $query = \Drupal::entityQuery($entity_info[0])
      ->condition('type', $entity_info[1])
      ->condition('status', 1);
    foreach ($filter['fields'] as $field_name => $value) {
      if (empty($value)) {
        continue;
      }
      if (is_array($value)) {
        $query->condition($field_name, $value, 'IN');
      } else {
        $query->condition($field_name, $value);
      }
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
