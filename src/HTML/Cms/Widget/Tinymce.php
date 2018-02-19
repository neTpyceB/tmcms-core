<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Widget;

use TMCms\HTML\Element;
use TMCms\HTML\Widget;
use TMCms\Templates\PageHead;

\defined('INC') or exit;

/**
 * Class Tinymce
 */
class Tinymce extends Widget
{
    protected $menubar = false;
    protected $statusbar = false;
    protected $plugins = [
        'stylebuttons',
        'textcolor',
        'colorpicker',
        'table',
        'image',
        'imagetools',
        'link',
        'hr',
        'code'
    ];
    protected $toolbar = [
        'undo redo',
        'styleselect',
        'bold italic underline',
        'style-h1 style-p hr table',
        'alignleft aligncenter alignright alignjustify',
        'bullist numlist',
        'link image code'
    ];
    protected $content_css = '';

    /**
     * @param Element $owner
     */
    public function __construct(Element $owner = null)
    {
        parent::__construct($owner);
    }

    /**
     *
     * @param Element $owner
     *
     * @return $this
     */
    public static function getInstance(Element $owner = null)
    {
        return new self($owner);
    }

    /**
     * @param bool $menubar
     *
     * @return $this
     */
    public function setMenubar(bool $menubar)
    {
        $this->menubar = $menubar;

        return $this;
    }

    /**
     * @param bool $statusbar
     *
     * @return $this
     */
    public function setStatusBar(bool $statusbar)
    {
        $this->statusbar = $statusbar;

        return $this;
    }

    /**
     * @param array $plugins
     *
     * @return $this
     */
    public function setPlugins(array $plugins)
    {
        $this->plugins = array_merge($this->plugins, $plugins);

        return $this;
    }

    /**
     * @param array $toolbar
     *
     * @return $this
     */
    public function setToolbar(array $toolbar)
    {
        $this->toolbar = $toolbar;

        return $this;
    }

    /**
     * @param string $content_css
     *
     * @return $this
     */
    public function setContentCss(string $content_css)
    {
        $this->content_css = $content_css;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        PageHead::getInstance()
            ->addJsUrl('//cdn.tinymce.com/4/tinymce.min.js')
            ->addJs("tinyMCE.PluginManager.add('stylebuttons', function(editor, url) {
                        ['pre', 'p', 'code', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'].forEach(function(name){
                            editor.addButton(\"style-\" + name, {
                                tooltip: \"Toggle \" + name,
                                text: name.toUpperCase(),
                                onClick: function() { editor.execCommand('mceToggleFormat', false, name); },
                                onPostRender: function() {
                                    var self = this, setup = function() {
                                        editor.formatter.formatChanged(name, function(state) {
                                            self.active(state);
                                        });
                                    };
                                    editor.formatter ? setup() : editor.on('init', setup);
                                }
                            })
                        });
                    });")
            ->addJs("tinymce.init({
                        selector: 'textarea#" . 'tinymce' . "',
                        setup: function (editor) {
                            editor.on('change', editor.save);
                        },
                        element_format: 'html',
                        relative_urls: false,
                        image_caption: true,
                        file_picker_callback: function(callback, value, meta) {
                            if (meta.filetype == 'image') {
                                var modalWindow = $('.mce-window[aria-label=\"Insert/edit image\"]');

                                popup_modal.result_element = modalWindow.find('label:contains(\"Source\")').next().find('input');

                                popup_modal.result_element.focus(function() {
                                    var image = new Image();

                                    image.onload = function() {
                                        modalWindow.find('input.mce-textbox[aria-label=\"Width\"]').val(this.width);
                                        modalWindow.find('input.mce-textbox[aria-label=\"Height\"]').val(this.height);
                                    };

                                    image.src = window.location.protocol + '//' + window.location.host + $(this).val();
                                });

                                popup_modal.show('?p=filemanager&nomenu&allowed_extensions=jpg,jpeg,bmp,tiff,tif,gif&cache=" . NOW . "', 700, 500);
                            }
                        },
                        formats: {
                            alignleft: { selector: 'img', styles: { 'float': 'left', 'margin': '0 1rem 1rem 0' } },
                            alignright: { selector: 'img', styles: { 'float': 'right', 'margin': '0 0 1rem 1rem' } }
                        },
                        menubar: " . json_encode($this->menubar) . ',
                        statusbar: ' . json_encode($this->statusbar) . ',
                        plugins: ' . json_encode($this->plugins) . ',
                        toolbar: ' . json_encode(implode(' | ', $this->toolbar)) . ",
                        content_css: '" . $this->content_css . "',
                    });");

        ob_start();
        return ob_get_clean();
    }
}
