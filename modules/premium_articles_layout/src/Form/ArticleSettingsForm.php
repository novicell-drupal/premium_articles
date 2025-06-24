<?php
namespace Drupal\premium_articles_layout\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ArticleSettingsForm extends ConfigFormBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, protected $typedConfigManager, DateFormatterInterface $dateFormatter) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('date.formatter')
    );
  }

  /**
   * @inheritDoc
   */
  protected function getEditableConfigNames()
  {
    return ['premium_articles_layout.settings'];
  }

  /**
   * @inheritDoc
   */
  public function getFormId()
  {
    return 'premium_articles_layout_settings';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('premium_articles_layout.settings');
    $form = [];

    $form['show_article_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show article title'),
      '#description' => $this->t('Check this box to display the article title.'),
      '#config_target' => 'premium_articles_layout.settings:show_article_title',
      '#default_value' => $config->get('show_article_title') ?? TRUE,
    ];

    $form['show_article_subtitle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show article subtitle'),
      '#description' => $this->t('Check this box to display the article subtitle. Requires a field_subtitle.'),
      '#config_target' => 'premium_articles_layout.settings:show_article_subtitle',
      '#default_value' => $config->get('show_article_subtitle') ?? TRUE,
    ];

    $form['show_article_type'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show article type'),
      '#description' => $this->t('Check this box to display the article type.'),
      '#config_target' => 'premium_articles_layout.settings:show_article_type',
      '#default_value' => $config->get('show_article_type') ?? TRUE,
    ];

    $form['show_article_categories'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show article categories'),
      '#description' => $this->t('Check this box to display the article categories.'),
      '#config_target' => 'premium_articles_layout.settings:show_article_categories',
      '#default_value' => $config->get('show_article_categories') ?? TRUE,
    ];

    $form['show_list_date'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show list date'),
      '#description' => $this->t('Check this box to display the list date.'),
      '#config_target' => 'premium_articles_layout.settings:show_list_date',
      '#default_value' => $config->get('show_list_date') ?? TRUE,
    ];

    $time = new DrupalDateTime();
    $format_types = \Drupal::entityTypeManager()->getStorage('date_format')->loadMultiple();
    $options = [];
    foreach ($format_types as $type => $type_info) {
      $format = $this->dateFormatter->format($time->getTimestamp(), $type);
      $options[$type] = $type_info->label() . ' (' . $format . ')';
    }

    $form['list_date_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Date format'),
      '#description' => $this->t("Choose a format for displaying the date. Be sure to set a format appropriate for the field, i.e. omitting time for a field that only has a date."),
      '#options' => $options,
      '#config_target' => 'premium_articles_layout.settings:list_date_format',
      '#default_value' => $config->get('list_date_format') ?: 'long',
    ];

    return parent::buildForm($form, $form_state);
  }

}
