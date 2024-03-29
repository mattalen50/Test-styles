(function ($, Drupal) {

  Drupal.behaviors.adminToolbarHelper = {
    attach: function (context, settings) {
      $('body.user-logged-in', context).each(function () {
		//when an a menu item is hovered
        $('.toolbar-menu-administration > ul > li .menu-item--expanded').on("mouseover", function () {
          //if wer aren't on mobile view
		  if ($('body').hasClass('toolbar-horizontal')) {
			//get the subnav ul, its parent and the top level menu item
			var parent = $(this);
            var subNav = $(this).children('.toolbar-menu');
            var expandedMenu = $('.toolbar-menu-administration > .toolbar-menu > .menu-item--expanded.hover-intent > ul');
            
			//check if there is a subnav
			//also check if the subnav already has a class and if so then we don't 
			//need to bother with the rest
            if (subNav.length && !subNav.hasClass('top') && !subNav.hasClass('bottom')) {
			  //add display block to it to avoid a jump
              subNav.css('display', 'block');
			  //get the positioning of the subnav
              var pos = subNav[0].getBoundingClientRect();
			  //check if the bottom of the subnav extends beyond the height of the window
              if (pos.bottom > window.innerHeight) {
                var threshold = pos.top;
                var buffer = 10;
                var diff = window.innerHeight - (pos.bottom + buffer);
                if (Math.abs(diff) > threshold) {
                  diff = '-' + (threshold + buffer);
                }
                if (Math.abs(diff) > 0) {
				  //add classes to the current subnav and parent
                  parent.addClass('menu-offscreen-parent');
                  subNav.addClass('menu-offscreen');
				  //check if the height of the subnav is greater than currently the expanded menu
				  //and make sure the expanded menu is smaller than the height of the window
                  if (subNav.height() > expandedMenu.height() || expandedMenu.height() > window.innerHeight) {
                    subNav.addClass('top');
                  } else {
                    subNav.addClass('bottom');
                  }
                }
              }
            }
          }
        });
      });
    }
  }

})(jQuery, Drupal);
