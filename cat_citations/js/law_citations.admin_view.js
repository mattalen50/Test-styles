(function ($, Drupal) {
  
  //convert elements in the Citations Admin view to select2
  //these elements, I couldn't change by setting $form['field_name']['#type'] = 'select2';
  //in law_editing.module
  Drupal.behaviors.citationsAdminExposed = {
    attach: function (context, settings) {
      $('#views-exposed-form-faculty-citations-citations-admin', context).each(function () {
        $("#edit-field-link-title-target-id, select[id^='edit-field-date-value-op'], select[id^='edit-action']").select2();
      })
    }
  }
  
})(jQuery, Drupal);