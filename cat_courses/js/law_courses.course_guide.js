(function ($, Drupal) {

  Drupal.behaviors.law_courses_course_guide = {
    attach: function (context, settings) {
      // Empty facets are hidden, but their paragraph wrappers are not.
      // Those wrappers can have unwanted margins or layout effects, so
      // we hide them.
      $('.facet-hidden', context).parents('.paragraph--type--view-placer').css('display', 'none');
    }
  };


})(jQuery, Drupal);
