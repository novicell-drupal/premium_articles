<?php
namespace Drupal\premium_articles;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Pager\Pager;
use Drupal\node\Entity\Node;
use JetBrains\PhpStorm\Pure;

class ArticleManager {

  /**
   * @var EntityStorageInterface
   */
  protected $taxonomyStorage;

  /**
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * @var EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory, EntityFieldManagerInterface $entityFieldManager, EntityTypeBundleInfoInterface $entityTypeBundleInfo) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->taxonomyStorage = $entityTypeManager->getStorage('taxonomy_term');
    $this->configFactory = $configFactory;
  }

  /**
   * @return array
   */
  public function getEntityBundles() {
    $list = $this->configFactory->listAll('premium_articles.');
    $result = [];
    foreach ($list as $config_id) {
      $config = $this->configFactory->get($config_id);
      $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($config->get('entity_type_id'));
      $result[$config->get('id')] = $bundle_info[$config->get('bundle')]['label'];
    }
    return $result;
  }

  public function getEntityBundleConfig($entity_bundle) {
    $config = $this->configFactory->get('premium_articles.' . $entity_bundle);
    return $config->getRawData();
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
    $definitions = $this->entityFieldManager->getFieldDefinitions($entity_info[0], $entity_info[1]);
    $definition = $definitions[$field_name];
    switch ($definition->getType()) {
      case 'entity_reference':
        $settings = $definition->getSettings() ?? [];
        $storage = $this->entityTypeManager->getStorage($settings['target_type']);
        // TODO: Support more entity types than taxonomy
        if ($settings['target_type'] == 'taxonomy_term') {
          if (count($settings['handler_settings']['target_bundles']) == 1) {
            $element['label'] = $definition->getLabel();
            $element['source'] = 'taxonomy_term';
            $element['options'] = [];
            $vid = reset($settings['handler_settings']['target_bundles']);
            $element['vid'] = $vid;
            $query = $storage->getQuery();
            $query->condition('vid', $vid)
              ->sort($settings['handler_settings']['sort']['field'], $settings['handler_settings']['sort']['direction']);
            $tids = $query->execute();
            $terms = $storage->loadMultiple($tids);
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

  public function getCountOptions() {
    return [
      5 => '5',
      10 => '10',
      15 => '15',
      20 => '20',
      25 => '25'
    ];
  }

  /**
   * Get list of supported sorting criteria
   *
   * @return array
   */
  public function getSortCriterias() {
    return [
      'newest' => t('Newest first'),
      'oldest' => t('Oldest first'),
      'alphabetical' => t('Alphabetical'),
    ];
  }

  /**
   * Get list of supported displays of totals
   *
   * @return array
   */
  public function getShowTotalOptions() {
    return [
      '' => t('None'),
      'filtered' => t('Filtered out of total number of items'),
      'shown' => t('Shown items out of filtered number of items'),
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
  public function getArticles($entity_bundle, array $filter = [], $page = 0) {
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
    } elseif (isset($filter['count']) && $filter['count'] > 0) {
      $query->range($page * $filter['count'], $filter['count']);
    }
    switch ($filter['sort']) {
      case 'alphabetical':
        $query->sort('title', 'ASC');
        break;
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

  /**
   * @param array $filter
   * @param int $shown
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function getArticlesTotal($entity_bundle, array $filter = [], $shown = 0) {
    $entity_info = explode('.', $entity_bundle);
    $count = 0;
    $total = 0;
    switch ($filter['show_total']) {
      case 'filtered':
        $query = \Drupal::entityQuery($entity_info[0])
          ->condition('type', $entity_info[1])
          ->condition('status', 1)
          ->count();
        $total = $query->execute();
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
        $count = $query->execute();
        break;
      case 'shown':
        $count = $shown;
        $query = \Drupal::entityQuery($entity_info[0])
          ->condition('type', $entity_info[1])
          ->condition('status', 1)
          ->count();
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
        $total = $query->execute();
        break;
    }
    return t('Showing @count out of @total', ['@count' => $count, '@total' => $total]);
  }

}
