<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Column;

use InvalidArgumentException;
use RuntimeException;
use TMCms\HTML\Cms\CmsForm;
use TMCms\HTML\Cms\Column;
use TMCms\HTML\Cms\Element\CmsCheckbox;
use TMCms\HTML\Cms\Element\CmsHtml;
use TMCms\HTML\Cms\Element\CmsInputRadio;
use TMCms\HTML\Cms\Element\CmsInputText;
use TMCms\HTML\Cms\Element\CmsRow;
use TMCms\HTML\Cms\Element\CmsSelect;
use TMCms\HTML\Cms\Element\CmsTextarea;
use TMCms\HTML\Cms\Linker;
use TMCms\HTML\Element;
use TMCms\HTML\Widget;
use TMCms\Templates\PageTail;

\defined('INC') or exit;

/**
 * Class ColumnInput
 */
class ColumnInput extends Column {
    const TABLE_ID_PREFIX = 'ID_PREFIX_';
    private static $allowed_input_types = [
        'text',
        'textarea',
        'radio',
        'checkbox',
        'select',
        'delete',
        'html'
    ];
    private static $js_loaded = [];

    private $selected_input_type = 'text';
    private $plugin_xeditable = false;
    private $disable_auto_name;
    private $disable_auto_form_tag;

    protected $widget;
    protected $disabled;

    /**
     * @param string $key
     * @return $this
     */
    public static function getInstance($key = '')
    {
        return new self($key);
    }

    /**
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setTypeText()
    {
        $this->setType('text');

        return $this;
    }

    /**
     * @param string $type
     *
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setType($type = '') {
        if (!\in_array($type, self::$allowed_input_types, true)) {
            throw new InvalidArgumentException('Unsupported column input type. Possible are: ' . implode(', ', self::$allowed_input_types));
        }

        $this->selected_input_type = $type;

        // Update some params based on type
        switch ($this->selected_input_type) {
            case 'checkbox': case 'radio': case 'delete':
                $this->setWidth('1%');
                $this->disableNewlines();
                $this->enableCenterAlign();
            break;
        }

        return $this;
    }

    /**
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setTypeTextarea() {
        $this->setType('textarea');

        return $this;
    }

    /**
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setTypeSelect() {
        $this->setType('select');

        return $this;
    }

    /**
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setTypeHtml()
    {
        $this->setType('html');

        return $this;
    }

    /**
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setTypeRadio() {
        $this->setType('radio');

        return $this;
    }

    /**
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setTypeCheckbox() {
        $this->setType('checkbox');

        return $this;
    }

    /**
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setTypeDelete() {
        $this->setType('delete');

        return $this;
    }

    /**
     * Special for type Select
     *
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options) {
        $this->options = $options;

        return $this;
    }

    /**
     *
     * @param int $row
     * @param array $row_data
     * @param Linker $linker
     *
     * @return string
     * @throws \RuntimeException
     */
    public function getView(int $row, array $row_data, Linker $linker): string
    {
        $this->id = $linker->table_id;
        $value = $this->getCellData($row_data);

        if (!isset($row_data['id'])) {
            throw new RuntimeException('ID must be supplied');
        }

        $name = $this->id .'[update]['. $row_data['id'] .']['. $this->key . ']';

        if ($this->disable_auto_name) {
            $name = $this->key;
        }

        switch ($this->selected_input_type) {
            default:
            case 'text':

                $cell_view = CmsInputText::getInstance($name)->setValue($value)
                ->setOnchange($this->getOnchange() ?: 'this.form.submit();');

                break;

            case 'select':

                // Build link from array
                if (\is_array($this->getHref())) {
                    $this->setHref(http_build_query($this->getHref()));
                }

                // Replace {%%} params
                $href = $this->replaceTemplateVars($row_data, $this->getHref());

                $cell_view = CmsForm::getInstance()
                    ->disableFullView()
                    ->setAction($href)
                    ->addField('', CmsRow::getInstance($name)->setValue(
                        CmsSelect::getInstance($this->key)
                            ->setName($name)
                            ->setOptions($this->options)
                            ->setSelected($value)
                            ->disableCustomStyled()
                            ->setOnchange($this->getOnchange() ?: 'this.form.submit();')
                        )
                    );

                if ($this->disable_auto_form_tag) {
                    $cell_view->disableFormTagOutput();
                }

                break;

            case 'textarea':

                $cell_view = CmsTextarea::getInstance($name)->setValue($value)
                    ->setOnchange($this->getOnchange() ?: 'this.form.submit();');

                break;

            case 'radio':

                $cell_view = CmsInputRadio::getInstance($this->key, $this->id . '[update][' . $row_data['id'] . ']')
                    ->setOnchange($this->getOnchange() ?: 'this.form.submit();')->setChecked((bool)$value);
                break;

            case 'checkbox':

                $cell_view = CmsCheckbox::getInstance($name)->setValue($value)
                    ->setOnchange($this->getOnchange() ?: 'this.form.submit();')->setChecked((bool)$value);

                break;
            case 'html':

                $cell_view = CmsHtml::getInstance($name)->setValue($value);

                break;

            case 'delete':

                $cell_view = '<a href="" onclick="table_form.remove_row(this);return false">x</a>';

                break;
        }

        if ($this->getIsReadonly()) {
            $cell_view->enableReadOnly();
        }

        // We have some widget attached to edit input
        if ($this->widget) {
            $cell_view->setWidget($this->widget);
            $widget = $cell_view->getWidget();
            if ($widget) {
                $cell_view = '<table width="100%"><tbody><tr><td>'. $cell_view .'</td><td width="20">'. $widget .'</td></tr></tbody></table>';
            }
        }
        if ($cell_view instanceof Element) {
            $cell_view->setAttribute('data-table-id', $this->id);
            $cell_view->setAttribute('data-id', $row_data['id']);
        }

        // Plugin for beautiful view
        if ($this->plugin_xeditable) {
            $cell_view->enableXEditable();
        }

        return $this->getCellView($cell_view, $row_data);
    }

    /**
     * @param Widget $widget
     * @return $this
     */
    public function setWidget(Widget $widget) {
        $this->widget = $widget;

        return $this;
    }

    /**
     * @param $tbl_id
     * @return null|void
     */
    public function getJs($tbl_id) {
        if (!empty(self::$js_loaded[$tbl_id])) {
            // Means JavaScript for current table was loaded already
            return;
        }

        self::$js_loaded[$tbl_id] = true;

        ?>
            <script>
                if (typeof table_form === 'undefined') {
                    table_form = {
                        remove_row: function(o) {
                            var node = jQuery(o).parent().parent().parent().parent();
                            var id = node.attr('id');
                            var tbl = 'table_form'+ id;
                            window[tbl].remove_row(o); // Request proper object with tbl id
                        },
                        tables: {},
                        prepareForSubmit: function() {
                            // Remove saved row in each table
                            while (jQuery('#id_-1').length) {
                                jQuery('#id_-1').remove();
                            }

                            // Prepare each table
                            var o, o2, el, inn, tbl;

                            for (el in table_form.tables) {
                                o = table_form.tables[el];
                                for (inn in o) {
                                    o2 = o[inn];
                                    tbl = 'table_form' + el;
                                    window[tbl].submit();
                                }
                            }
                        }
                    };
                }

                var table_form<?= $tbl_id ?> = {
                    row: null,
                    id_prefix: '<?= self::TABLE_ID_PREFIX ?>',
                    next_id: 0,
                    removed_ids: [],
                    remove_row: function(o) {
                        var id;
                        var node;

                        if (!confirm('<?= __('Are you sure?') ?>')) {
                            return;
                        }

                        node = jQuery(o).parent().parent();
                        id = node.attr('id');

                        node.remove();

                        // Save removed ID to send in POST
                        if (id.indexOf('[add]') !== -1) {
                            return;
                        }

                        id = id.split('_')[1];

                        this.removed_ids.push(id);

                        table_form.tables['<?=$tbl_id?>'].push(id);
                    },
                    add_row: function() {
                        var node = this.row.clone();
                        var new_id = this.next_id++;
                        var row_name, attr, new_o;

                        // Replace IDs, names and values in new row
                        jQuery(node).find('input, textarea, select').each(function() {
                            if (this.type !== 'button') { // Inputs
                                jQuery(this).attr({'name':jQuery(this).attr('name').replace('<?=$tbl_id?>[update][-1][', '<?=$tbl_id?>[add]['+ new_id +'][')});

                                if (this.type === 'radio') {
                                    this.value = '<?=$tbl_id?>[add]['+ table_form<?=$tbl_id?>.id_prefix + new_id +']';
                                }

                                this.id = table_form<?=$tbl_id?>.id_prefix + new_id + this.id;

                                // Saving name for Widget
                                row_name = this.name.split('][');
                                row_name = row_name[row_name.length-1];
                                row_name = row_name.substring(0, row_name.length-1);
                            } else {
                                // Widgets, set widget button return value
                                attr = jQuery(this).attr('onclick').replace('<?=$tbl_id?>_update_1_'+ row_name +'_', table_form<?=$tbl_id?>.id_prefix+ new_id +'<?=$tbl_id?>_update_1_'+ row_name +'_');

                                jQuery(this).attr('onclick', attr);

                                // Special for IE - recreating element to get onclick working
                                if (jQuery.browser.msie) {
                                    this.setAttribute('onclick', new Function (attr));
                                    new_o = table_form<?=$tbl_id?>.changeInputType(this, 'button');
                                    jQuery(this).replaceWith(new_o);
                                }
                            }
                        });

                        jQuery(node).attr('id', 'id_'+ table_form<?=$tbl_id?>.id_prefix + new_id);

                        jQuery('#<?=$tbl_id?>').append(node);
                    },
                    submit: function(action) {
                        // Remove saved row
                        var id, el;
                        var form;
                        jQuery('#id_-1').remove();

                        // Create elements to submit
                        if (action) {
                            form = document.createElement('form');
                            form.action = action;
                            form.method = 'post';
                        } else {
                            form = document.createElement('div');
                        }

                        var o, new_o, skip;

                        // Draw added and updated data
                        jQuery('#<?= $tbl_id ?>').find('input[type!="button"], textarea, select').each(function() {
                            o = this;
                            if (o.tagName.toLowerCase() === 'textarea') {
                                new_o = document.createElement('input');
                                new_o.value = jQuery(this).val();
                                new_o.name = o.name;
                                o = new_o;
                            } else if (o.type.toLowerCase() === 'radio') {
                                if (!o.checked) {
                                    skip = true;
                                } else {
                                    new_o = document.createElement('input');
                                    new_o.name = jQuery(this).val() + '['+ o.name +']';
                                    new_o.value = 1;
                                    o = new_o;
                                }
                            } else if (o.type.toLowerCase() === 'checkbox') {
                                if (!o.checked) {
                                    skip = true;
                                }
                            } else if (o.type.toLowerCase() === 'select-one') {
                                new_o = document.createElement('input');
                                new_o.value = jQuery(this).val();
                                new_o.name = o.name;
                                o = new_o;
                            }

                            o = table_form<?=$tbl_id?>.changeInputType(o, 'hidden');

                            if (!skip) {
                                form.appendChild(o);
                            }

                            skip = false;
                        });

                        // Draw removed ids
                        for (el in this.removed_ids) {
                            id = parseInt(this.removed_ids[el], 10);
                            if (!id) {
                                continue;
                            }
                            new_o = document.createElement('input');
                            new_o = table_form<?=$tbl_id?>.changeInputType(new_o, 'hidden');
                            new_o.value = id;
                            new_o.name = '<?=$tbl_id?>[remove]['+ id +']';
                            form.appendChild(new_o);
                        }

                        // For IE - we have to put the form into HTML
                        document.getElementById('<?=$tbl_id?>').appendChild(form);
                        if (action) {
                            form.submit();
                        }
                    },
                    init: function() {
                        // Save table id for late post
                        table_form.tables['<?=$tbl_id?>'] = [];

                        // Save last row to be copied
                        var $o = jQuery('#<?=$tbl_id?> > tbody > tr:last');

                        this.row = $o.clone();

                        $o.remove();
                    },
                    // Special for IE - changing type of input
                    changeInputType: function(oldObject, oType) {
                        var newObject = document.createElement('input');
                        newObject.type = oType;

                        if (oldObject.onmouseover) {
                            newObject.onmouseover = oldObject.onmouseover;
                        }
                        if (oldObject.onclick) {
                            newObject.onclick = oldObject.onclick;
                        }
                        if (oldObject.size) {
                            newObject.size = oldObject.size;
                        }
                        if (oldObject.value) {
                            newObject.value = oldObject.value;
                        }
                        if (oldObject.name) {
                            newObject.name = oldObject.name;
                        }
                        if (oldObject.id) {
                            newObject.id = oldObject.id;
                        }
                        if (oldObject.className) {
                            newObject.className = oldObject.className;
                        }

                        return newObject;
                    }
                };

                // Start script
                table_form<?= $tbl_id ?>.init();
            </script>
        <?php
    }

    /**
     * @return $this
     */
    public function enableXEditable() {
        PageTail::getInstance()
            ->addCssUrl('plugins/x-editable/bootstrap-editable.css')
            ->addCssUrl('plugins/x-editable/bootstrap-editable.min.js')
        ;

        $this->plugin_xeditable = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function disableAutoName()
    {
        $this->disable_auto_name = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function disableAutoFormTag()
    {
        $this->disable_auto_form_tag = true;

        return $this;
    }
}
