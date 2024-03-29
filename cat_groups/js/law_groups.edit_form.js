(function($, Drupal){
  /**
   * Automatically submit the Entity Group Field selection widget when
   * a selection is made. This prevents a situation where the editor makes
   * a group selection in the dropdown but forgets to hit the widget "Submit" to
   * actually record that group selection.
   */
  Drupal.behaviors.entityGroupFieldAutoSubmit = {
    attach: function(context, settings) {
      $('.form-item-entitygroupfield-add-more-add-relation select', context).bind('change', function(ev){
        $(this).parent().siblings('.form-submit').trigger('mousedown');
      });
    }
  };
})(jQuery, Drupal);
