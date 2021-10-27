Drupal.behaviors.article_pager = {
  attach: function (context, settings) {
    jQuery('.article-form-contents .pager .pager__item a', context).click(function(e) {
      e.preventDefault();
      let searchParams = new URLSearchParams(jQuery(this).attr('href'));
      let form = jQuery(this).closest('.article-form');
      let submit = form.find('.article-page-submit');
      let page = form.find('.article-page-value');
      page.val(searchParams.get('page'));
      submit.trigger( "click" )
    });
  }
};
