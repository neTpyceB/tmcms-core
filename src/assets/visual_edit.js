cms_visual_edit = {
	default_string: '',

	init: function () {
		$('.cms_visual_editable').css({
			'outline': '1px solid blue',
			'cursor': 'pointer'
		});
	},

	edit: function (obj) {
		$('.cms_visual_editable').css('outline', '1px solid blue');
		obj.contentEditable = true;
		obj.style.outline = '1px solid green';
		obj.style.cursor = 'default';
		cms_visual_edit.load_btn(obj);

		obj.focus();

		// load text to temp variable
		this.default_string = $(obj).html();
	},
	save: function (obj_id) {
		var $obj = $('#'+ obj_id);

		$obj.css({
			'outline': '1px solid orange',
			'cursor': ''
		});

		$.ajax({
			type: "POST",
			url: '/-/api/visual_edit_save/',
			data: {
				content : $obj.html(),
				component : $obj.data("component"),
				page_id: $obj.data("page_id"),
				type: $obj.data("type")
			},
			dataType: 'json',
			complete: function (data) {
				if (data.status)
				{
					$obj.css('outline', '1px solid blue');
				}
			},
			error: function () {
				$obj.css('outline', '1px solid red');
			}
		});

		// back to default state
		cms_visual_edit.init();
		cms_visual_edit.hide_btn();
	},
	reset: function (obj_id) {
		var $obj = $('#'+ obj_id);
		$obj.html(cms_visual_edit.default_string);

		// back to default state
		cms_visual_edit.init();
	},
	load_btn: function (obj) {
		// Remove existing buttons
		this.hide_btn();

		// Add to current container
		$(obj).after('<div id="visual_btn" style="position:absolute; z-index: 9999; background-color: #FFFFFF;";><button id="visual_save" style="padding: 3px; margin-top: 3px;" onclick="cms_visual_edit.save(\''+ obj.id +'\')">Update</button>' +
		'<button style="padding: 3px; margin-top: 3px;" onclick="cms_visual_edit.reset(\''+ obj.id +'\')">Reset</button></div>');
	},

	hide_btn: function () {
		$('#visual_btn').remove();
	}
};

$(function() {
	cms_visual_edit.init();

	var $element = $('.cms_visual_editable');

	$(document).on('click', function (e) {
		// if element is opened and click target is outside it
		if ($element.is(':visible') && !$element.is(e.target) && !$element.has(e.target).length) {
			// to default statement
			cms_visual_edit.init();
			cms_visual_edit.hide_btn();
		}
	});

});