cms_clickmap_path = cms_clickmap_id = '';

$(function() {
	/* Creating functions */
	(function($) {

        $.fn.saveClicks = function() {
            //var lastCoords = [];
            $(this).data('lastCoords',{});
            $(this).bind('mousedown.clickmap', function(evt) {
                var lastCoords = $(this).data('lastCoords');
                if (lastCoords.pageX != evt.pageX && lastCoords.pageY != evt.pageY) {
                    $.post('/-/ajax/clickmap_register/', {
                        x: evt.pageX,
                        y: evt.pageY,
                        l: cms_page_id
                    });
                    $(this).data('lastCoords', {
                        pageX: evt.pageX,
                        pageY: evt.pageY
                    });
                }
            });
        };

    })(jQuery);

	$(document).saveClicks();
});