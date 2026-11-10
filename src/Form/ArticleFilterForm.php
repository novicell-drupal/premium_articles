<?php
namespace Drupal\premium_articles\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_overview\Form\OverviewFilterForm;
use Drupal\entity_overview\OverviewFilter;

class ArticleFilterForm extends OverviewFilterForm {

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
   * @param \Drupal\entity_overview\OverviewFilter|null $filter
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state, OverviewFilter $filter = NULL) {
    $form = parent::buildForm($form, $form_state, $filter);
    $form['#attributes']['class'][] = 'article-form';
    return $form;
  }

}
