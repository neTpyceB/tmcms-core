var cms_plugins = {
    render_component: function(el) {
        var $el = jQuery(el);
        var val = $el.val();
        var page_id = $el.data('page_id');
        var name = $el.attr('name');

        jQuery.get('?p=structure&do=_ajax_render_plugin_fields&nomenu&id='+ page_id +'&file='+ val +'&name='+ name, function(res) {
            var $incoming = jQuery(res).find('fieldset');
            var $parent_tr = $el.closest('tr');
            var $fieldset = $parent_tr.next('tr').find('fieldset');
            if ($fieldset.length) {
                $fieldset.replaceWith($incoming);
            } else {
                $parent_tr.closest('table').append($incoming.closest('tr'));
            }
        });
    }
}