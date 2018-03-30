var cms_plugins = {
    render_component: function(el) {
        var $el = $(el);
        var val = $el.val();
        var page_id = $el.data('page_id');
        var name = $el.attr('name');

        $.get('?p=structure&do=_ajax_render_plugin_fields&nomenu&id=' + page_id + '&file=' + val + '&name=' + name, function (res) {
            var $incoming = $(res).find('fieldset');
            var $parent_container = $el.closest('.form-body');
            var $fieldset = $parent_container.find('fieldset');
            if ($fieldset.length) {
                $fieldset.replaceWith($incoming);
            } else {
                $parent_container.find('.form-control').prepend($incoming);
            }
        });
    }
};
