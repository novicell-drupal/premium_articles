<?php
namespace Drupal\premium_articles\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
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
  public function buildForm(array $form, FormStateInterface $form_state, $options = []) {
    $form['#attributes']['class'][] = 'article-form';
    $form['#cache']['tags'][] = 'node_list';

    if (is_null($form_state->get('page'))) {
      $form_state->set('page', $this->request->query->get('page') ?? 0);
    }
    $form_state->set('types',  $this->request->query->get('types') ?? $form_state->get('types') ?? $options['types'] ?? []);
    $form_state->set('categories',  $this->request->query->get('categories') ?? $form_state->get('categories') ?? $options['categories'] ?? []);
    $form_state->set('count',  $this->request->query->get('count') ?? $form_state->get('count') ?? $options['count'] ?? 5);
    $form_state->set('view_mode',  $form_state->get('view_mode') ?? $options['view_mode'] ?? 'teaser');
    $form_state->set('pagination',  $form_state->get('pagination') ?? $options['pagination'] ?? FALSE);

    if ($form_state->hasValue('types')) {
      if (empty($form_state->getValue('types'))) {
        $form_state->set('types', []);
      } elseif (!is_array($form_state->getValue('types'))) {
        $form_state->set('types', [$form_state->getValue('types')]);
      } else {
        $types = [];
        foreach ($form_state->getValue('types') as $key => $value) {
          if ($value) {
            $types[] = $key;
          }
        }
        $form_state->set('types', $types);
      }
    }

    if ($form_state->hasValue('categories')) {
      if (empty($form_state->getValue('categories'))) {
        $form_state->set('categories', []);
      } elseif (!is_array($form_state->getValue('categories'))) {
        $form_state->set('categories', [$form_state->getValue('categories')]);
      } else {
        $form_state->set('categories', $form_state->getValue('categories'));
      }
    }

    if ($form_state->hasValue('count')) {
      if (empty($form_state->getValue('count'))) {
        $form_state->set('count', 0);
      } else {
        $form_state->set('count', intval($form_state->getValue('count')));
      }
    }

    $form['filters'] = [];
    if (!empty($options['type_filter'])) {
      $form['filters']['types'] = [
        '#type' => 'checkboxes',
        '#options' => $this->articleManager->getTypes(),
        '#default_value' => empty($form_state->get('types')) ? [] : $form_state->get('types'),
        '#ajax' => [
          'callback' => '::contentCallback',
          'event' => 'change',
          'wrapper' => 'article-form-contents',
          'progress' => [
            'type' => 'throbber',
          ],
        ]
      ];
    }

    if (!empty($options['category_filter'])) {
      $radios = [
        0 => $this->t('All')
      ];
      $radios += $this->articleManager->getCategories();
      $form['filters']['categories'] = [
        '#type' => 'radios',
        '#options' => $radios,
        '#default_value' => empty($form_state->get('categories')) ? 0 : reset($form_state->get('categories')),
        '#ajax' => [
          'callback' => '::contentCallback',
          'event' => 'change',
          'wrapper' => 'article-form-contents',
          'progress' => [
            'type' => 'throbber',
          ],
        ]
      ];
    }

    if (!empty($options['count_filter'])) {
      $form['filters']['count'] = [
        '#type' => 'select',
        '#options' => $this->articleManager->getCountOptions(),
        '#default_value' => empty($form_state->get('count')) ? 5 : $form_state->get('count'),
        '#ajax' => [
          'callback' => '::contentCallback',
          'event' => 'change',
          'wrapper' => 'article-form-contents',
          'progress' => [
            'type' => 'throbber',
          ],
        ]
      ];
    }

    if (!empty($form['filters'])) {
      $form['filters']['#type'] = 'container';
    }

    $page = $form_state->get('page') ?? 0;
    $form['content'] = $this->buildContents($form_state);

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
      'types' => $form_state->get('types'),
      'categories' => $form_state->get('categories'),
      'count' => $form_state->get('count'),
      'view_mode' => $form_state->get('view_mode'),
      'pagination' => $form_state->get('pagination'),
    ];
    $page = $form_state->get('page') ?? 0;

    if (!$this->request->isXmlHttpRequest() || $form_state->isRebuilding()) {
      $nodes = $this->articleManager->getArticles($options, $page);
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
    $content['articles'] = \Drupal::entityTypeManager()->getViewBuilder('node')->viewMultiple($nodes, $options['view_mode']);

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
