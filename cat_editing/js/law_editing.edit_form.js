(function($, Drupal){

  Drupal.behaviors.layoutParagraphsUx = {
    attach: function(context, settings) {
      /**
       * Bundle type label
       *
       * Decorate every layout paragraph with its bundle type.
       */
      $('.paragraph--view-mode--preview', context).each(function(){
        const element = $('<div class="law-editing-preview-label"></div>');
        const label = $(this).attr('data-bundle-label');
        element.text(label);
        $(this).prepend(element);
      });
      /**
       * Slick carousel
       *
       * Slideshows are not rendering correctly when they are not in the
       * initial edit tab. We use an InteractionObserver to re-initialize
       * the slideshow when the Layout Paragraphs container is visible.
       */
      $('.paragraph--view-mode--preview', context).each(function () {
        if ($(this).find('.slick').length > 0) {
          let callback = (entries) => {
            entries.forEach(entry => {
              if (entry.intersectionRatio > 0) {
                // This redraws the Slick carousel.
                $(entry.target).find('.slick-slider').slick('setPosition');
                // We probably don't need to do this more than once, so
                // turn off the observer after it fires.
                observer.disconnect();
              }
            });
          };
          let observer = new IntersectionObserver(callback, {
            root: document.querySelector('#edit-field-components-wrapper'),
            threshold: 1
          });
          observer.observe(this);
        }
      });
    }
  };

  Drupal.behaviors.recrutingEventDateHelper = {
    attach: function(context, settings) {
      //modify editing experience on the node form for recruiting events.
      $('form[class^="node-recruiting-event"].node-form', context).each(function() {

        //because the end date in a date range field cannot be toggled on and off
        //add a 'show end date' checkbox which is checked by default.
        $('#edit-field-event-date-0 .fieldset-wrapper').prepend('<div class="form-item form-type-checkbox form-item-field-event-date-show-todate"><input type="checkbox" id="edit-field-event-date-show-todate" name="field_event_date_show_todate" value="1" checked="checked" class="form-checkbox">  <label class="option" for="edit-field-event-date-show-todate">Show End Date </label></div>');
        
        //get the initial values in the start date/time and end/date time fields
        const startDate = $('#edit-field-event-date-0-value-date').val();
        const startTime = $('#edit-field-event-date-0-value-time').val();
        const endDate = $('#edit-field-event-date-0-end-value-date').val();
        const endTime = $('#edit-field-event-date-0-end-value-time').val();

        /**
        * check if the start date and time is the same as the end date and time. 
        * also check if the title field is empty so we know if this is an previously created event or not.
        * if all of this is true, we know that it is a previously created event with the same start and end time
        * so we uncheck the checkbox and hide the end date/time fields.
        */ 
        if(startDate === endDate && startTime === endTime && $('#edit-title-0-value').val() !== '') {
          $('#edit-field-event-date-show-todate').prop('checked', false);
          $('#edit-field-event-date-0 #edit-field-event-date-0-end-value').hide().prev().hide();
        }

        //when the the checkbox is checked or unchecked
        $('#edit-field-event-date-show-todate').change(function() {
          if(this.checked) {
            //show the end date field. need to use prev() function to select the End Date label because it only has a generic class
            $('#edit-field-event-date-0 #edit-field-event-date-0-end-value').show().prev().show();

            //remove the event listeners
            $("#edit-field-event-date-0-value-date").off("change");
            $("#edit-field-event-date-0-value-time").off("change");
          } else {
            //hide the end date field. need to use prev() function to select the End Date label because it only has a generic class
            $('#edit-field-event-date-0 #edit-field-event-date-0-end-value').hide().prev().hide();

            // sync the start and end fields in case the editor checks the box after editing the start date/time
            $("#edit-field-event-date-0-end-value-date").val($('#edit-field-event-date-0-value-date').val());
            $("#edit-field-event-date-0-end-value-time").val($('#edit-field-event-date-0-value-time').val());

            //add event listeners to the start date/time fields.
            //and sync the values of the fields to the end date/time fields
            $("#edit-field-event-date-0-value-date").on("change", function() {
              $("#edit-field-event-date-0-end-value-date").val(this.value);
            });
            $("#edit-field-event-date-0-value-time").on("change", function() {
              $("#edit-field-event-date-0-end-value-time").val(this.value);
            });
          }
        });
      
      });
    }
  };

  //when add section link is clicked from course guide admin, select the course in the course reference field
  Drupal.behaviors.addCourseSection = {
    attach: function (context, settings) {
      $('.node-course-section-form.node-form', context).each(function () {

        if (getParameterByName('field_course')) {
			var course = getParameterByName('field_course');
			$(document).ready(function(){
				$("#edit-field-course").val(course);
				$("#edit-field-course").trigger('change');
			});
		}

        // Read a page's GET URL variables and return them as an associative array.
        function getParameterByName(name, url = window.location.href) {
          name = name.replace(/[\[\]]/g, '\\$&');
          var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
            results = regex.exec(url);
          if (!results) return null;
          if (!results[2]) return '';
          return decodeURIComponent(results[2].replace(/\+/g, ' '));
        }
      });
    }
  };

})(jQuery, Drupal);
