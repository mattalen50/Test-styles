(function ($, Drupal) {

  //js for the admin course guide
  Drupal.behaviors.courseGuideSearchHelper = {
    attach: function (context, settings) {
      $("form[id^='views-exposed-form-admin-course-guide']", context).each(function () {

        //apply select2 to operator fields
        $("select[id^='edit-field-start-semester-tid-op'], select[id^='edit-field-course-type-ref-tid-op'], select[id^='edit-field-instructors-target-id-op'], select[id^='edit-field-multi-semester-value'], select[id^='edit-field-grade-base-target-id-op'], select[id^='edit-field-graduation-requirements-target-id-op'], select[id^='edit-field-student-year-target-id-op'], select[id^='edit-field-student-year-override-value-op'], select[id^='edit-field-limited-drop-value'], select[id^='edit-field-prerequisites-default-value-op'], select[id^='edit-field-course-equivalency-value-op'], select[id^='edit-field-wilson-reserves-uri-op'], select[id^='edit-field-twen-site-uri-op'], select[id^='edit-field-related-links-uri-op'], select[id^='edit-field-syllabus-target-id-op'], select[id^='edit-changed-op'], select[id^='edit-status-1'], select[id^='edit-action']").select2();
        //apply select2 to fields paired with operator fields
        $("select[id^='edit-field-start-semester-tid'], select[id^=edit-title-op], select[id^='edit-field-course-type-ref-tid'], select[id^='edit-field-grade-base-target-id'], select[id^='edit-field-graduation-requirements-target-id'], select[id^='edit-field-student-year-target-id'], select[id^='edit-field-subject-area-target-id']").select2({
          placeholder: "Choose some options"
        });
        
        //add advanced-field class to fields in the advanced search section to hide them
        $("div.js-form-item-field-credits-text-value-selective, div.js-form-item-field-multi-semester-value, fieldset[id^='edit-field-grade-base-target-id-wrapper'], fieldset[id^='edit-field-graduation-requirements-target-id-wrapper'], fieldset[id^='edit-field-student-year-target-id-wrapper'], fieldset[id^='edit-field-student-year-override-value-wrapper'], div.js-form-item-field-limited-drop-value, fieldset[id^='edit-field-prerequisites-default-value-wrapper'], fieldset[id^='edit-field-course-equivalency-value-wrapper'], fieldset[id^='edit-field-subject-area-target-id-wrapper'], fieldset[id^='edit-field-wilson-reserves-uri-wrapper'], fieldset[id^='edit-field-twen-site-uri-wrapper'], fieldset[id^='edit-field-related-links-uri-wrapper'], fieldset[id^='edit-field-syllabus-target-id-wrapper'], fieldset[id^='edit-changed-wrapper'], div.js-form-item-status-1, fieldset[id^='edit-changed-wrapper']").addClass('advanced-field');

        //when basic search or advanced search are clicked
        $('.exposed-search-options a.search-option').click(function (ev) {
          ev.preventDefault();
          //remove is-active class from all elements that have it
          $('.exposed-search-options .is-active').removeClass('is-active');
          //add is-active class to the link and the parent li
          $(this).addClass('is-active').parent().addClass('is-active');
          //hide or show fields based on which link was clicked
          if ($(this).hasClass('advanced')) {
            $('.advanced-field').show();
          } else if ($(this).hasClass('basic')) {
            $('.advanced-field').hide();
          }
        });
      })
    }
  };

})(jQuery, Drupal);
