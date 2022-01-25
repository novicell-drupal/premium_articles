<?php
namespace Drupal\premium_articles\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\premium_articles\ArticleManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ArticleFilterForm extends FormBase {

  /**
   * @var ArticleManager
   */
  protected $articleManager;

  /**
   * @var Request
   */
  protected $request;

  function __construct(ArticleManager $articleManager, RequestStack $requestStack) {
    $this->articleManager = $articleManager;
    $this->request = $requestStack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('premium_articles.manager'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'premium_articles_filter_form';
  }

  /**
   * Builds the article filter form.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param array $options
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $options = []) {
    $form['#attributes']['class'][] = 'article-form';
    $form['#cache']['tags'][] = 'node_list';

    $values = $options;
    $values['page'] = 0;
    unset($values['facets']);
    unset($values['_attributes']);
    foreach ($values as $key => $value) {
      if (!$form_state->has($key)) {
        $form_state->set($key, $value);
      }
      if ($this->request->query->has($key)) {
        $form_state->set($key, $this->request->query->get($key));
      }
      if ($form_state->hasValue($key)) {
        if (is_array($form_state->getValue($key))) {
          $result = [];
          foreach ($form_state->getValue($key) as $value2) {
            if ($value2) {
              $result[] = $value2;
            }
          }
          $form_state->set($key, $result);
        } else {
          $form_state->set($key, $form_state->getValue($key));
        }
      }
    }
    foreach ($values['fields'] as $key => $value) {
      if ($this->request->query->has($key)) {
        $form_state->set(['fields', $key], $this->request->query->get($key));
      }
      if ($form_state->hasValue($key)) {
        if (is_array($form_state->getValue($key))) {
          $result = [];
          foreach ($form_state->getValue($key) as $value2) {
            if ($value2) {
              $result[] = $value2;
            }
          }
          if ($form_state->get(['fields', $key]) != $result) {
            $form_state->set('page', 0);
            $form_state->set(['fields', $key], $result);
          }
        } else {
          if ($form_state->get(['fields', $key]) != $form_state->getValue($key)) {
            $form_state->set('page', 0);
            $form_state->set(['fields', $key], $form_state->getValue($key));
          }
        }
      }
    }

    $ajax = [
      'callback' => '::contentCallback',
      'event' => 'change',
      'wrapper' => 'article-form-contents',
      'progress' => [
        'type' => 'throbber',
      ],
    ];
    $form['facets'] = [];
    $fields = $this->articleManager->getFieldFormElements($options['entity_bundle']);
    foreach ($fields as $field_name => $form_element) {
      if (in_array($field_name, $options['facets'])) {
        $form['facets'][$field_name] = [
          '#type' => $form_element['form_element'],
          '#title' => $form_element['label'],
          '#options' => $form_element['options'],
          '#ajax' => $ajax
        ];
        if ($form_state->has(['fields', $field_name])) {
          $form['facets'][$field_name]['#default_value'] = $form_state->get(['fields', $field_name]);
        }
      }
    }

    if (in_array('count', $options['facets'])) {
      $form['facets']['count'] = [
        '#type' => 'select',
        '#options' => $this->articleManager->getCountOptions(),
        '#default_value' => empty($form_state->get('count')) ? 5 : $form_state->get('count'),
        '#ajax' => $ajax
      ];
    }

    if (in_array('sort', $options['facets'])) {
      $form['facets']['sort'] = [
        '#type' => 'select',
        '#options' => $this->articleManager->getSortCriterias(),
        '#default_value' => empty($form_state->get('sort')) ? 'newest' : $form_state->get('sort'),
        '#ajax' => $ajax
      ];
    }

    if (!empty($form['facets'])) {
      $form['facets']['#type'] = 'container';
    }

    $form['content'] = $this->buildContents($form_state);

    $page = $form_state->get('page') ?? 0;
    if ($form_state->get('pagination')) {
      $form['#attached']['library'] = ['premium_articles/pager'];
      $form['page'] = [
        '#type' => 'hidden',
        '#attributes' => ['class' => ['article-page-value']],
        '#default_value' => $page
      ];
      $form['page_submit'] = [
        '#type' => 'submit',
        '#attributes' => ['class' => ['visually-hidden', 'article-page-submit']],
        '#submit' => ['::pageSubmit'],
        '#value' => $page,
        '#ajax' => [
          'callback' => '::contentCallback',
          'event' => 'click',
          'wrapper' => 'article-form-contents',
          'progress' => [
            'type' => 'throbber',
          ],
        ]
      ];
    }

    return $form;
  }

  /**
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function buildContents(FormStateInterface $form_state) {
    $options = [
      'fields' => $form_state->get('fields'),
      'count' => $form_state->get('count'),
      'view_mode' => $form_state->get('view_mode'),
      'pagination' => $form_state->get('pagination'),
      'sort' => $form_state->get('sort'),
      'show_total' => $form_state->get('show_total')
    ];
    $entity_bundle = $form_state->get('entity_bundle') ?? 'node.article';
    $page = $form_state->get('page') ?? 0;

    if (!$this->request->isXmlHttpRequest() || $form_state->isRebuilding()) {
      $nodes = $this->getArticlesForBuilding($entity_bundle, $options, $page);
    } else {
      $nodes = [];
    }
    $content = [
      '#type' => 'container',
      '#attributes' => [
        'id' => "article-form-contents",
        'class' => ['article-form-contents']
      ],
    ];
    $this->buildArticlesInContent($content, $nodes, $options);

    if (!empty($options['show_total'])) {
      $content['total'] = [
        '#markup' => $this->articleManager->getArticlesTotal($entity_bundle, $options, count($nodes))
      ];
    }

    if ($options['pagination']) {
      if (!$this->request->isXmlHttpRequest() || $form_state->isRebuilding()) {
        $content['pager'] = [
          '#type' => 'pager'
        ];
      }
    }
    return $content;
  }

  /**
   * Function for retrieving the articles to be displayed. Overwrite for when a custom query is necessary.
   *
   * @param string $entity_bundle
   * @param array $options
   * @param int $page
   *
   * @return \Drupal\node\Entity\Node[]
   */
  protected function getArticlesForBuilding($entity_bundle, array $options, $page = 0) {
    return $this->articleManager->getArticles($entity_bundle, $options, $page);
  }

  /**
   * Function for building the display of the articles. Overwrite for building overviews with custom layouts and views.
   *
   * @param array $content
   * @param Node[] $nodes
   * @param array $options
   */
  protected function buildArticlesInContent(array &$content, array $nodes, array $options) {
    $content['articles'] = \Drupal::entityTypeManager()->getViewBuilder('node')->viewMultiple($nodes, $options['view_mode']);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Submit function for changing page via AJAX.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function pageSubmit(array &$form, FormStateInterface $form_state) {
    $page = intval($form_state->getValue('page'));
    $form_state->set('page', $page);
    $form_state->setRebuild();
  }

  /**
   * AJAX callback for refreshing content.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function contentCallback($form, FormStateInterface $form_state) {
    return $form['content'];
  }
}
