<?php
namespace Drupal\premium_articles\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_overview\Form\OverviewFilterForm;

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
   * @param array $options
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $options = []) {
    $form = parent::buildForm($form, $form_state, $options);
    $form['#attributes']['class'][] = 'article-form';
    return $form;
  }

}
