<?php

namespace Drupal\Tests\premium_articles\Unit;

use Drupal\Core\Form\FormState;
use Drupal\entity_overview\Entity\Overview;
use Drupal\entity_overview\OverviewFilter;
use Drupal\entity_overview\OverviewManager;
use Drupal\premium_articles\Form\ArticleFilterForm;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the article form's integration with Entity Overview filters.
 *
 * @group premium_articles
 */
#[Group('premium_articles')]
class ArticleFilterFormTest extends UnitTestCase {

  /**
   * Tests accepting an OverviewFilter while retaining the article form markup.
   */
  public function testBuildFormWithOverviewFilter(): void {
    $overview = $this->createMock(Overview::class);
    $filter = $this->getMockBuilder(OverviewFilter::class)
      ->setConstructorArgs(['articles', ['count' => 10]])
      ->onlyMethods(['getOverview'])
      ->getMock();
    $filter->method('getOverview')->willReturn($overview);

    $manager = $this->createMock(OverviewManager::class);
    $manager->method('deepLinksEnabled')->willReturn(FALSE);
    $manager->expects($this->once())->method('getAllFieldInfos')
      ->with($overview)->willReturn([]);
    $request_stack = new RequestStack();
    $request_stack->push(new Request());

    // The content query is outside this form contract. Exercise the real
    // parent form builder and filter handling without querying content.
    $form_object = $this->getMockBuilder(ArticleFilterForm::class)
      ->setConstructorArgs([$manager, $request_stack])
      ->onlyMethods(['buildContents'])
      ->getMock();
    $form_object->method('buildContents')->willReturn(['#markup' => 'Articles']);
    $form_state = new FormState();
    $form = $form_object->buildForm([], $form_state, $filter);

    $this->assertSame('premium_articles_filter_form', $form_object->getFormId());
    $this->assertSame('articles', $form_state->get('overview'));
    $this->assertSame(10, $form_state->get('count'));
    $this->assertSame('articles', $form['#overview']);
    $this->assertContains('overview-form', $form['#attributes']['class']);
    $this->assertContains('article-form', $form['#attributes']['class']);
    $this->assertSame(['#markup' => 'Articles'], $form['content']);
  }

}
