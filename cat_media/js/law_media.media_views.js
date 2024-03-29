(function($, Drupal){

  Drupal.behaviors.showWarnings = {
    attach: function(context, settings) {
    $('select#edit-action').change(function() {
      var selected_option = $('#edit-action option:selected').text();
      if(selected_option == 'Change owner') {
        $('.media-change-warning').show();
      } else {
        $('.media-change-warning').hide();
      }
    });
    $('#views-bulk-operations-configure-action #edit-media-image-field-selector-uid, #views-bulk-operations-configure-action #edit-media-document-field-selector-uid, #views-bulk-operations-configure-action #edit-media-remote-video-field-selector-uid, #views-bulk-operations-configure-action #edit-media-video-field-selector-uid').prop( "checked", true );
    $('#views-bulk-operations-configure-action details').attr('open', '');
    }
  };

  })(jQuery, Drupal);
