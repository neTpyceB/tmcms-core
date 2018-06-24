<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms;

use RuntimeException;
use TMCms\HTML\Cms\Column\ColumnOrder;
use TMCms\Strings\Converter;
use TMCms\Strings\Translations;

\defined('INC') or exit;

/**
 * Class Column
 * @package TMCms\HTML\Cms
 */
abstract class Column
{
    protected $id = '';

    protected $key = '';
    protected $title = '';
    protected $value = '';
    protected $options = [];

    protected $width = '';
    protected $align = 'left';
    protected $nowrap = false;
    protected $special_characters_allowed = false;
    protected $is_readonly = false;

    protected $href_parse = false;
    protected $href = '';
    protected $href_new_window = false;
    protected $href_confirm = false;

    protected $style = '';
    protected $intersect = [];

    protected $callback_values = [];
    protected $callbacks_for_row = [];
    protected $onclick = '';
    protected $onchange = [];

    protected $enable_auto_total_in_column = false;

    /**
     * @var float
     */
    protected $total_sum = 0.00;
    protected $total_avg = 0.00;
    protected $filter_sum = 0.00;
    protected $filter_avg = 0.00;

    protected $black_tag_list = [];
    protected $white_tag_list = [];

    protected $help = false;
    protected $help_text = '';
    protected $help_href = '';
    protected $help_href_new_window = false;

    protected $sql_order_by = '';
    protected $sql_order_by_direction = '';
    /**
     * @var string
     */
    protected $sql_order_by_direction_default = '';

    protected $tooltip_field = '';
    protected $tooltip_field_colspan = 1;
    protected $translation = false;
    protected $data_attributes = [];
    protected $column_data_pairs = [];

    protected $enable_encryption = false;
    protected $nl2br = false;
    private $cut_long_strings = true;
    private $cut_long_strings_limit = 50;

    /**
     * @var callable
     */
    protected $column_availability_check_callback;
    protected $table_id;
    private $ajax = false;

    /**
     * Sets column key and title from key
     *
     * @param string $key
     */
    public function  __construct(string $key)
    {
        $this->key = $key;

        $this->setTitle(__(Converter::charsToNormalTitle($key)));
    }

    /**
     * Set td title
     *
     * @param string $title
     *
     * @return $this
     */
    public function setTitle(string $title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Overwrite it in extended columns
     *
     * @param string $table_id
     *
     * @return string
     */
    public function getJs(string $table_id): string {
        return '';
    }

    /**
     * Overwrite it in extended columns
     *
     * @param array $row_data
     *
     * @return string
     */
    public function getRowStyle(array $row_data): string {
        return '';
    }

    /**
     * Get td title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return $this
     */
    public function enableTranslationColumn()
    {
        $this->translation = true;

        return $this;
    }

    /**
     * Makes column using Order with default key
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function enableOrderableColumn()
    {
        $this->setOrderBy('`' . $this->getKey() . '`');

        return $this;
    }

    /**
     * @return array
     */
    public function getOrderBy(): array
    {
        return ['by' => $this->sql_order_by, 'direction' => $this->sql_order_by_direction ? strtoupper($this->sql_order_by_direction) : '', 'applied' => $this->sql_order_by_direction];
    }

    /**
     * Get/Set SQL ORDER BY
     *
     * @param string $sql_expr SQL expression to pass to ORDER BY without ASC or DESC, will replace current. Pass false (like 0 or "") value to remove ordering.
     * @param string $direction_for_default Is this column default and its direction. Possible values are 'asc' and 'desc', meaning that ordering is done by this column and direction is the one passed.
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setOrderBy(string $sql_expr = '', string $direction_for_default = '')
    {
        $this->sql_order_by = $sql_expr ?: '';

        if ($direction_for_default) {
            $direction_for_default = strtolower($direction_for_default);

            if ($direction_for_default !== 'asc' && $direction_for_default !== 'desc') {
                throw new \InvalidArgumentException('$is_default_and_direction can be only be a string and possible values are "asc" or "desc"');
            }

            $this->sql_order_by_direction_default = $direction_for_default;
        }

        $this->sql_order_by_direction = '';
        if ($sql_expr && !empty($_GET['order_by']) && preg_match('/^' . $this->key . '\-(asc|desc)$/i', $_GET['order_by'], $matches)) {
            $this->sql_order_by_direction = $matches[1];
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    public function removeOrderByDefault()
    {
        $this->sql_order_by_direction_default = '';
    }

    /**
     * @return string
     */
    public function getOrderByDefault(): string
    {
        return $this->sql_order_by_direction_default;
    }

    /**
     * @return array
     */
    public function getOnchange(): array
    {
        return $this->onchange;
    }

    /**
     * Set onchange event for row
     *
     * @param string JS script
     *
     * @return $this
     */
    public function setOnchange($js)
    {
        $this->onchange = (array)str_replace("'", "\'", $js);

        return $this;
    }

    /**
     * @return $this
     */
    public function enableNarrowWidth()
    {
        $this->setWidth('1%');

        return $this;
    }

    /**
     * @return string
     */
    public function getWidth(): string
    {
        return $this->width;
    }

    /**
     * @param string $width
     *
     * @return $this
     */
    public function setWidth(string $width)
    {
        $this->width = $width;

        return $this;
    }

    /**
     * @return $this
     */
    public function enableAutoTotalInColumn() {
        $this->enable_auto_total_in_column = true;

        return $this;
    }

    /**
     * Set total sum of column values.
     *
     * @param float|bool $sum
     *
     * @return $this
     */
    public function setSumTotal($sum)
    {
        $this->total_sum = $sum;

        return $this;
    }

    /**
     * @return float|bool
     */
    public function getSumTotal()
    {
        return $this->total_sum;
    }

    /**
     * @return float|bool
     */
    public function getAvgTotal()
    {
        return $this->total_avg;
    }

    /**
     * Set average sum of column values
     *
     * @param float|bool $avg
     *
     * @return $this
     */
    public function setAvgTotal($avg)
    {
        $this->total_avg = $avg;

        return $this;
    }

    /**
     * @return float|bool
     */
    public function getSumFiltered()
    {
        return $this->filter_sum;
    }

    /**
     * Set total sum of filtered column values
     *
     * @param float|bool $sum
     *
     * @return $this
     */
    public function setSumFiltered($sum)
    {
        $this->filter_sum = $sum;

        return $this;
    }

    /**
     * @return float|bool
     */
    public function getAvgFiltered()
    {
        return $this->filter_avg;
    }

    /**
     * Set average sum of filtered column values
     *
     * @param float|bool $avg
     *
     * @return $this
     */
    public function setAvgFiltered($avg)
    {
        $this->filter_avg = $avg;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setValue(string $value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @param bool $flag
     *
     * @return $this
     */
    public function setIsReadonly(bool $flag) {
        $this->is_readonly = $flag;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsReadonly(): bool
    {
        return $this->is_readonly;
    }

    /**
     * @return $this
     */
    public function enableAjax()
    {
        $this->ajax = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function disableAjax()
    {
        $this->ajax = false;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAjax(): bool
    {
        return $this->ajax;
    }

    /**
     * Set flag for htmlspecialchars for td value
     *
     * @return bool
     */
    public function isHtmlAllowed(): bool
    {
        return $this->special_characters_allowed;
    }

    /**
     * @return $this
     */
    public function allowHtml()
    {
        $this->special_characters_allowed = true;

        return $this;
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setTableId(string $id)
    {
        $this->table_id = $id;

        return $this;
    }

    /**
     * Set list of tags that should be escaped
     *
     * @param array $tags
     *
     * @throws RuntimeException
     */
    public function setBlackTagList(array $tags)
    {
        if (!$this->special_characters_allowed) {
            throw new RuntimeException('All special chars will be escaped. Set AllowHtml to use black list');
        }

        if ($this->white_tag_list) {
            throw new RuntimeException('White list already added.');
        }

        $this->black_tag_list = $tags;
    }

    /**
     * Set list of tags that should NOT be escaped
     * @param array $tags
     *
     * @throws \RuntimeException
     */
    public function setWhiteTagList(array $tags)
    {
        if (!$this->special_characters_allowed) {
            throw new RuntimeException('All special chars will be escaped. Set AllowHtml to use white list');
        }

        if ($this->black_tag_list) {
            throw new RuntimeException('Black list already added.');
        }

        $this->white_tag_list = $tags;
    }

    /**
     * Question sign near column title with help text showing on mouse hover
     *
     * @param string $text text to show
     * @param string $click_href link to go onclick
     * @param bool $href_new_window open link in new window
     *
     * @return $this
     * @throws \RuntimeException
     */
    public function setHelpText(string $text = '', string $click_href = '', bool $href_new_window = false)
    {
        $this->help_href_new_window = $href_new_window;

        $text = trim($text);
        $click_href = trim($click_href);

        if ($text === '' && $click_href === '') {
            throw new RuntimeException('Help must contain text or href');
        }

        if ($click_href === '' && $href_new_window) {
            throw new RuntimeException('Help error. $href_new_window parameter cannot be used without $click_href parameter.');
        }

        $this->help = true;
        $this->help_text = $text;

        if ($click_href !== '') {
            $this->help_href = $click_href;
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function enableRightAlign()
    {
        $this->setAlign('right');

        return $this;
    }

    /**
     * @return $this
     */
    public function enableCenterAlign()
    {
        $this->setAlign('center');

        return $this;
    }

    /**
     * @return string
     */
    public function getAlign(): string
    {
        return $this->align;
    }

    /**
     * Set td align
     *
     * @param string $align
     *
     * @return $this
     */
    public function setAlign(string $align)
    {
        $this->align = $align;

        return $this;
    }

    /**
     * Set td style
     *
     * @param string $style
     *
     * @return $this
     */
    public function setStyle(string $style)
    {
        $this->style = $style;

        return $this;
    }

    /**
     * @return $this
     */
    public function disableNewlines()
    {
        $this->nowrap = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function disableCutLongStrings()
    {
        $this->cut_long_strings = false;

        return $this;
    }

    /**
     * @param int $limit
     *
     * @return $this
     */
    public function setCutLongStringsLimit(int $limit = 50)
    {
        $this->cut_long_strings_limit = $limit;

        return $this;
    }

    /**
     * Set td data href confirm dialog
     *
     * @param bool $confirm
     *
     * @return $this
     */
    public function setHrefConfirm(bool $confirm)
    {
        $this->href_confirm = $confirm;

        return $this;
    }

    /**
     * Set td data href target='_blank' flag
     *
     * @param bool $flag
     *
     * @return $this
     */
    public function setOpenHrefInNewWindow(bool $flag)
    {
        $this->href_new_window = $flag;

        return $this;
    }

    /**
     * Get html td title
     *
     * @return string
     */
    public function getTitleView(): string
    {
        if ($this->help) {
            $help_box_id = 'column_' . $this->key . '_hint_box';
            if ($this->help_href) {
                $help_title = '<a href="' . $this->help_href . '"' . ($this->help_href_new_window ? ' target="_blank"' : '') . '>?</a>';
            } else {
                $help_title = '<span onmouseover="HintHelp.show(\'' . $help_box_id . '\', \'' . htmlspecialchars($this->help_text, ENT_QUOTES) . '\', event)" onmouseout="HintHelp.hide(\'' . $help_box_id . '\')">?</span>';
            }
            $help_html = '&nbsp;<sup style="cursor: pointer; position: relative;" id="' . $help_box_id . '">' . $help_title . '</sup>';
        } else {
            $help_html = '';
        }

        if ($this->sql_order_by) {
            if ($this->sql_order_by_direction) {
                $order_by_direction = '&nbsp;<small>' . ($this->sql_order_by_direction === 'asc' ? '▲' : '▼') . '</small>';
            } elseif (isset($this->sql_order_by_direction_default)) {
                $order_by_direction = '&nbsp;<small>' . ($this->sql_order_by_direction_default === 'asc' ? '▲' : '▼') . '</small>';
            }

            $order_by_url = Linker::makeUrl(array_merge($_GET, ['order_by' => $this->key . '-' . ($this->sql_order_by_direction === 'asc' ? 'desc' : 'asc')]));
        } else {
            $order_by_url = '';
        }

        return '<span style="font-weight:700">'
            . ($order_by_url ? '<a href="' . $order_by_url . '">' : '')
            . __($this->getTitle()) . ($order_by_url ? '</a>' : '')
            . ($order_by_direction ?? '')
            . '</span>'
            . $help_html;
    }

    /**
     * @param array $intersect_data
     *
     * @return $this
     */
    public function setIntersectData(array $intersect_data)
    {
        $this->intersect = $intersect_data;

        return $this;
    }

    /**
     * Set callback function array
     *
     * @param string|array|callable $function
     *
     * @return $this
     */
    public function addCallback($function)
    {
        $this->callback_values[] = $function;

        return $this;
    }

    /**
     * Set callback (with full row data) function array
     *
     * @param string|callable $function
     *
     * @return $this
     */
    public function setCallableForRow($function)
    {
        $this->callbacks_for_row[] = $function;

        return $this;
    }

    /**
     * Return html td
     *
     * @param string $cell_data
     * @param array $row_data
     *
     * @return string
     */
    public function getCellView($cell_data, array $row_data = [])
    {
        if ($this->tooltip_field && $row_data) {
            $this->requireTooltipJavascript();
            $tooltip = $this->replaceTemplateVars($row_data, $this->tooltip_field);

            if ($tooltip) {
                return '<td' . $this->getAttributesView(0, $row_data) . ' tooltip="' . nl2br(htmlspecialchars($tooltip, ENT_QUOTES)) . '" onmouseover="cmsTooltip.show(this,' . $this->tooltip_field_colspan . ')" onmouseout="cmsTooltip.hide(this)">' . $cell_data . '</td>';
            }
        }

        // Order for drag-and-drop
        if ($this instanceof ColumnOrder && isset($row_data['order']) && $this->isDragable()) {
            $this->addDataAttribute('order', $row_data['order']);
        }

        // Cut long strings
        if ($this->cut_long_strings && $cell_data === strip_tags((string)$cell_data)) {
            $cell_data = Converter::cutLongStrings((string)$cell_data, $this->cut_long_strings_limit);
        }

        return '<td' . $this->getAttributesView(false, $row_data) . '>' . $cell_data . '</td>';
    }

    private function requireTooltipJavascript()
    {
        if (isset($this->tooltipJavascriptRequired)) {
            return;
        }

        ?>
        <style>
            #cmsTooltipDivText {
                margin: 1px 6px 6px;
                padding-top: 6px;
            }

            #cmsTooltipDiv {
                position: absolute;
                visibility: hidden;
                text-align: left;
                background: #fff;
                border: 2px solid #777;
                box-shadow: 2px 2px 2px;
                border-radius: 6px;
            }

            #cmsTooltipDiv li {
                margin-left: 20px;
            }
        </style>
        <div id="cmsTooltipDiv">
            <div id="cmsTooltipDivText"></div>
        </div>
        <script>
            var cmsTooltip = {
                show: function (o, colspan) {
                    var t = document.getElementById('cmsTooltipDiv');
                    var $o = jQuery(o);
                    var positions = $o.offset();
                    var width = 0;
                    var i = 1;

                    document.getElementById('cmsTooltipDivText').innerHTML = o.getAttribute('tooltip');

                    $(t).appendTo('body');

                    o = $o;
                    while (i <= colspan && o.length) {
                        width += jQuery(o).width();
                        o = jQuery(o).next();
                        i++;
                    }

                    var border_spacing = $o.closest('TABLE').css('border-spacing');

                    if (border_spacing) {
                        width += parseInt(border_spacing, 10) * (colspan - 1);
                    }

                    t.style.width = width + 12 * colspan;
                    t.style.maxWidth = $o.width() - 5 + 'px';
                    t.style.visibility = 'visible';

                    $('#cmsTooltipDiv').stop().show();

                    var $el = jQuery('#cmsTooltipDiv');

                    $el.css('left', positions['left'] + 10).css('top', positions['top'] + $o.height() + 15);
                },
                hide: function (o) {
                    $('#cmsTooltipDiv').stop().hide();
                    o.style.fontWeight = '';
                }
            };
            var cumulativeOffset = function (element) {
                var top = 0;
                var left = 0;

                do {
                    top += element.offsetTop || 0;
                    left += element.offsetLeft || 0;
                    element = element.offsetParent;
                } while (element);

                return {
                    top: top,
                    left: left
                };
            };

        </script><?php

        $this->tooltipJavascriptRequired = true;
    }

    /**
     * @param array $row_data
     * @param string $field
     *
     * @return string
     */
    protected function replaceTemplateVars(array $row_data, string $field)
    {
        $pairs = [];

        foreach ($row_data as $k => $v) {
            if (!$this->isHtmlAllowed()) {
                $v = htmlspecialchars((string)$v, ENT_QUOTES);
            }
            $pairs['{%' . $k . '%}'] = $v;
        }

        if (strpos($field, '{%') !== false) {
            return strtr($field, $pairs);
        }

        return $field;
    }

    /**
     * Get td attributes
     *
     * @param string $style_to_replace
     * @param array $row_data
     *
     * @return string
     */
    public function getAttributesView($style_to_replace = '', array $row_data = [])
    {
        $attr = [];

        if ($this->width) {
            $attr['width'] = 'width="' . $this->width . '"';
        }
        if ($this->align) {
            $attr['align'] = 'align="' . $this->align . '"';
        }
        if ($this->nowrap) {
            $attr['nowrap'] = 'nowrap="nowrap"';
        }
        if ($this->onclick) {
            $attr['onclick'] = 'onclick="' . ($row_data ? $this->replaceTemplateVars($row_data, $this->getOnclick()) : $this->onclick) . '"';
        }
        if ($this->onchange) {
            $attr['onchange'] = 'onchange="' . ($row_data ? $this->replaceTemplateVars($row_data, implode(';', $this->getOnchange())) : implode(';', $this->getOnchange())) . '"';
        }
        if ($this->key) {
            $attr['data-column_key'] = 'data-column_key="' . $this->key . '"';
        }
        if ($this instanceof ColumnOrder && $row_data) {
            $attr['data-order'] = 'data-order="' . $this->getCellData($row_data) . '"';
        }
        if (isset($row_data['id'])) {
            $attr['data-id'] = 'data-id="' . $row_data['id'] . '"';
        }

        if ($style_to_replace) {
            $attr['style'] = 'style="' . $style_to_replace . '"';
        } elseif ($this->style) {
            $attr['style'] = 'style="' . $this->style . '"';
        }

        if (\count($attr) === 0) {
            return '';
        }

        return ' ' . implode(' ', $attr) . $this->getDataAttributesHtml($row_data);
    }

    /**
     * @param array $row_data
     * @param string $key
     *
     * @return string
     */
    public function getCellData(array $row_data, string $key = ''): string
    {
        if (!$key) {
            $key = $this->key;
        }

        // Parse {%template%} variables
        if (isset($row_data[$key])) {
            $value = $row_data[$key];
        } else {
            $value = $this->replaceTemplateVar($row_data);
        }

        // Run callback for values in column
        $value = $this->runCallback($row_data, (string)$value, $key);

        if ($this->translation && $value && ctype_digit($value)) {
            $translation = Translations::get($value, LNG);
            if ($translation) {
                $value = $translation;
            }
        }

        if (isset($this->intersect[$value])) {
            $value = $this->intersect[$value];
        }

        // Escape values
        if ($this->special_characters_allowed) {
            $value = $this->escapeSpecTags($value);
        } else {
            $value = htmlspecialchars((string)$value);
        }

        // Extract paired data
        $paired_data = $this->getPairedDataOptionsForKeys();
        if ($paired_data && isset($paired_data[$value])) {
            $value = $paired_data[$value];
        }

        // Newlines
        if ($this->nl2br) {
            $value = nl2br($value);
        }

        return $value;
    }

    /**
     * @param array $row_data
     *
     * @return string
     */
    private function replaceTemplateVar($row_data): string
    {
        return $this->replaceTemplateVars($row_data, $this->value);
    }

    /**
     * @param array $row
     * @param string $value
     * @param string $key
     *
     * @return string
     */
    private function runCallback(array $row, string $value, string $key = '')
    {
        // Callback for all data
        foreach ($this->callback_values as $function) {
            if (\is_string($function) && substr_count($function, '::') === 1) {
                $value = \call_user_func(explode('::', $function), $value, $key);
            } elseif (\is_array($function)) {
                if (\count($function) === 2) {
                    $value = $function($value);
                }
            } else {
                $value = $function($value);
            }
        }

        // Callback for one row
        foreach ($this->callbacks_for_row as $function) {
            if (\is_string($function) && substr_count($function, '::') === 1) {
                $value = \call_user_func(explode('::', $function), $value, $row, $key);
            } else {
                $value = $function($value, $row);
            }
        }

        return $value;
    }

    /**
     * @param string $value
     *
     * @return string escaped text
     */
    private function escapeSpecTags($value): string
    {
        if ($this->black_tag_list) {
            SpecTags::addBlackList($this->black_tag_list);
        } elseif ($this->white_tag_list) {
            SpecTags::addWhiteList($this->white_tag_list);
        }

        return SpecTags::escape($value);
    }

    /**
     * @return array
     */
    public function getPairedDataOptionsForKeys(): array
    {
        return $this->column_data_pairs;
    }

    /**
     * @param array $row_data
     *
     * @return string
     */
    public function getDataAttributesHtml(array $row_data = []): string
    {
        if (!$row_data) {
            $row_data = [];
        }

        $html = [];

        foreach ($this->data_attributes as $k => $v) {
            $html[] = ' data-' . $k . '="' . $this->replaceTemplateVars($row_data, $v) . '"';
        }

        return implode(' ', $html);
    }

    /**
     * Adds attribute to HTML like data-id, data-hover etc
     *
     * @param string $postfix
     * @param string $value
     *
     * @return $this
     */
    public function addDataAttribute($postfix, $value = '')
    {
        $this->data_attributes[$postfix] = $value;

        return $this;
    }

    /**
     * @param string $field
     * @param int $colspan
     *
     * @return $this
     */
    public function setTooltip(string $field, int $colspan = 0)
    {
        $field = trim($field);
        if (!$field) {
            return $this;
        }

        $this->tooltip_field = $field;

        if ($colspan > 0) {
            $this->tooltip_field_colspan = $colspan;
        }

        return $this;
    }

    /**
     * @param int    $row
     * @param array  $row_data
     * @param Linker $linker
     *
     * @return string
     */
    abstract public function getView(int $row, array $row_data, Linker $linker): string;

    /**
     * @param array $column_data_pairs options for column paired keys
     * @return $this
     */
    public function setPairedDataOptionsForKeys(array $column_data_pairs)
    {
        $this->column_data_pairs = $column_data_pairs;

        // Remove _id part for title
        $this->setTitle(str_replace(' id', '', $this->getTitle()));

        return $this;
    }

    /**
     * @return $this
     */
    public function enableDecryption()
    {
        $this->enable_encryption = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function enableNl2Br()
    {
        $this->nl2br = true;

        return $this;
    }

    /**
     * @param array $row_data
     * @param Linker $linker       - TableLinker
     * @param array $linker_array - array for TableLinker link builder
     *
     * @return string
     */
    protected function getParsedHref(array $row_data, Linker $linker, array $linker_array = []): string
    {
        if ($this->href) {
            return $this->replaceHrefParams($row_data);
        }

        return $linker->getHrefWithDoAppend($linker_array);
    }

    /**
     * @param string $href
     *
     * @return $this
     */
    public function setHref(string $href)
    {
        $this->href = $href;

        return $this;
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function replaceHrefParams(array $params): string
    {
        return $this->replaceTemplateVars($params, $this->href);
    }


    /**
     * @return string
     */
    public function getHref(): string
    {
        return $this->href;
    }

    /**
     * @param string $value
     * @param string $href
     *
     * @return string
     */
    protected function getHrefView(string $value, string $href): string
    {
        $confirm = $this->href_confirm ? str_replace(['"', "'"], ['', "\'"], __('Are you sure?')) : '';
        $href_view = '<a href="'
            . $href
            . '" class="nounderline"'
            . ($confirm ? ' onclick="return confirm(\'' . $confirm . '\');"' : '')
            . ($this->href_new_window ? ' target="_blank"' : '') . '>'
            . $value
            . '</a>';

        return $href_view;
    }

    /**
     * @return string
     */
    public function getOnclick(): string
    {
        return \is_array($this->onclick) ? implode(';', $this->onclick) : $this->onclick;
    }

    /**
     * @return string
     */
    public function getOnclickString(): string
    {
        return implode(';', $this->getOnclick());
    }
}
