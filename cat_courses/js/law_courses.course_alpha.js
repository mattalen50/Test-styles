(function ($, Drupal) {

  Drupal.behaviors.law_courses_course_alpha = {
    attach: function (context, settings) {
      // Disable unused glossary links.
      $('.glossary-nav a', context).each(function(index){
        let letter = $(this).text();
        if (!document.getElementById(letter)) {
          $(this).addClass('disabled').removeAttr('href').bind('click', function(ev){
            ev.stopPropagation();
            ev.preventDefault();
            return false;
          });
        }
      });
    }
  };


})(jQuery, Drupal);
