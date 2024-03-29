(function($, Drupal) {

Drupal.behaviors.frontpage = {
  attach: function (context, settings) {
    // When there aren't enough events in Featured Events, we need to
    // pad the listings by inserting Event Filler storage entities from a
    // hidden view on the page.
    var eventCount = $('.view-events > .view-content > .views-row', context).length;
    var eventFillerCount = $('.view-events-filler > .view-content > .views-row', context).length;
    // The expected 'view-content' container isn't rendered when there are no rows. Let's ensure that it's always there.
    if (eventCount === 0) {
      $('.view-events', context).append('<div class="view-content"></div>');
    }
    while (eventCount < 3 && eventFillerCount > 0) {
      $('.view-events-filler > .view-content > .views-row').first().appendTo('.view-events > .view-content');
      eventFillerCount--;
      eventCount++;
    }
  }
};


})(jQuery, Drupal);
