<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Column;
use InvalidArgumentException;
use RuntimeException;
use TMCms\HTML\Cms\CmsTable;
use TMCms\HTML\Cms\Column;
use TMCms\HTML\Cms\Linker;

/**
 * Class ColumnTree
 * @package TMCms\HTML\Cms\Column
 */
class ColumnTree extends Column
{
    private static $js_loaded = false;
    protected $id_column = 'id';
    protected $pid_column = 'pid';
    protected $show_key = '';
    protected $data_url = '';
    private $child_ids = [];
    private $child_padding = 15;
    private $js_object_name = '';
    private $ajax = false;
    private $ajax_path = '';
    private $save_inner_state = true;
    private $_max_level = 0;
    private $_top_level_id = '0';

    /**
     * @param string $key
     */
    public function  __construct(string $key)
    {
        parent::__construct($key);

        $this->show_key = $key;
        $this->js_object_name = '_' . md5($key);
    }

    /**
     * @param string $key
     *
     * @return $this
     */
    public static function getInstance($key)
    {
        return new self($key);
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
     *
     * @param int $id
     *
     * @return $this
     */
    public function setTopLevelId(int $id = 0)
    {
        $this->_top_level_id = (string)$id;

        return $this;
    }

    /**
     * @param int $width
     *
     * @return $this
     */
    public function setChildPadding(int $width)
    {
        $this->child_padding = abs($width);

        return $this;
    }

    /**
     * @param string $key
     *
     * @return $this
     */
    public function setShowKey(string $key)
    {
        $this->show_key = $key;

        return $this;
    }

    /**
     * @return string
     */
    public function getIdColumn(): string
    {
        return $this->id_column;
    }

    /**
     * @param string
     *
     * @return $this
     */
    public function setIdColumn(string $column)
    {
        $this->id_column = $column;

        return $this;
    }

    /**
     * @return string
     */
    public function getPidColumn(): string
    {
        return $this->pid_column;
    }

    /**
     * @param string $column
     *
     * @return $this
     */
    public function setPidColumn(string $column)
    {
        $this->pid_column = $column;

        return $this;
    }

    /**
     * @param array $row_data
     *
     * @return string
     */
    public function getRowId(array $row_data): string
    {
        return CmsTable::TR_ID_PREFIX . $this->getCellData($row_data, $this->id_column);
    }

    /**
     * @param array $row_data
     *
     * @return string
     */
    public function getRowStyle(array $row_data): string
    {
        if ($this->ajax || (isset($row_data[$this->pid_column]) && (string)$row_data[$this->pid_column] === $this->_top_level_id)) {
            return '';
        }

        return 'display: none;';
    }

    /**
     * This is required for Tree + to work
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        return $this->ajax;
    }

    /**
     * @return string
     */
    public function getAjaxPath()
    {
        return $this->ajax_path;
    }

    /**
     * @param bool $flag
     *
     * @return $this
     */
    public function setSaveInnerState(bool $flag)
    {
        $this->save_inner_state = $flag;

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
     * @param $table_id
     *
     * @return string
     */
    public function getJs(string $table_id): string
    {
        ob_start();
        $this->getTreeJs();

        if (!$this->ajax_path) {
            $this->ajax_path = '?p='. P .'&do=' . P_DO;
        }

        ?>
        <script>
            var <?= $this->js_object_name ?> =
            new TableTree('<?= $table_id ?>', <?=json_encode($this->child_ids)?>, <?= json_encode($this->js_object_name, JSON_OBJECT_AS_ARRAY) ?>, '<?= $this->ajax_path ?>', <?= (int)$this->ajax ?>, <?= (int)$this->save_inner_state ?>);
        </script><?php

        return ob_get_clean();
    }

    private function getTreeJs()
    {
        if (self::$js_loaded) {
            return;
        }
        ?>
        <script>
            var TableTree = function (table_id, ids, var_name, ajax_path, ajax, save_inner) {
                this.storage_key = 'opened_keys';
                this.storage_key_collapsed = 'collapsed_keys';
                this.id = table_id;
                this.ids = ids;
                this.o_name = var_name;
                this.storage = new Storage(table_id);
                this.ajax_path = ajax_path;
                this.expandedAjaxIds = {};
                this.expandAjaxLoading = [];
                this.save_inner = save_inner;
                if (save_inner) {
                    this.collapsed = this.getCollapsedOpenedId();
                } else {
                    this.collapsed = [];
                }
                if (ajax) {
                    this.openAjaxNodes();
                } else {
                    this.openNodes();
                }
            };
            TableTree.prototype.toggle = function (id) {
                if (document.getElementById('toggle_' + id).innerHTML === '+') this.expand(id);
                else this.collapse(id);
            };
            TableTree.prototype.toggleAjax = function (id) {
                if (document.getElementById('toggle_' + id).innerHTML === '-') {
                    this.collapse(id);
                } else this.expandAjax(id);
            };
            TableTree.prototype.expandAjax = function (id) {
                if (this.expandedAjaxIds[id]) {
                    this.expand(id);
                } else {
                    var lvl = this.ids[id].level;
                    if (!in_array(id, this.expandAjaxLoading)) {
                        this.expandAjaxLoading.push(id);
                        document.getElementById('toggle_' + id).innerHTML = '<img src="<?=DIR_CMS_IMAGES_URL?>ajax-loader.gif" alt="loading">';
                    }
                    var this_o = this;
                    jQuery.ajax({
                        type: "GET",
                        url: this.ajax_path + '&nomenu=1&ajax_tree=1&ajax_tree_node_lvl=' + lvl + '&ajax_tree_ids=' + id,
                        success: function (data) {
                            this_o.expandAjaxResult(id, data);
                        }
                    });
                }
            };
            TableTree.prototype.expandAjaxResult = function (id, html) {
                if (!html || this.expandedAjaxIds[id] === true) return;
                // redraw table - insert in the right position new rows
                var div_o = document.getElementById('htmlgen_table_container');
                var tbl = div_o.innerHTML;

                var id_pos = tbl.indexOf('id_' + id + '"');
                if (id_pos === -1) id_pos = tbl.indexOf('id_' + id + ' ');
                var cur_tr_end_pos = tbl.toLowerCase().indexOf('</tr>', id_pos) + 5;
                div_o.innerHTML = tbl.substr(0, cur_tr_end_pos) + html + tbl.substr(cur_tr_end_pos);

                // refresh ids array (add new nodes)
                var tbl_o = document.getElementById(this.id);
                var new_rows_cnt = tbl_o.rows.length;
                var row_id;
                var lvl = this.ids[id].level + 1;
                for (var i = 0; i < new_rows_cnt; i++) {
                    row_id = tbl_o.rows[i].id.substr(3);
                    if (!row_id) continue;
                    if (!this.ids[row_id]) {
                        this.ids[row_id] = {
                            'level': lvl,
                            'children': []
                        };
                        this.ids[id].children.push(row_id);
                    }
                }
                // save opened ajax id
                this.expandedAjaxIds[id] = true;
                array_kick_by_value(this.expandAjaxLoading, id);
                this.expand(id);
            };
            TableTree.prototype.expandAjaxOpenedIds = function (result) {
                // there are 2 parst in result - new js ids and html
                if (!result) return;
                var js_end_pos = result.indexOf('\n');
                if (js_end_pos === -1) return;
                try {
                    var js_ids = eval('(' + result.substr(0, js_end_pos) + ')');
                } catch (e) {
                    return;
                }
                var html = result.substr(js_end_pos + 1);
                if (!html) return;

                // draw new table content
                var div_o = document.getElementById('htmlgen_table_container');
                var tbl = div_o.innerHTML;
                var tbody_start_pos = tbl.indexOf('id_') - 3;
                var begin = tbl.substr(0, tbody_start_pos);
                var tbody_pos = begin.toLowerCase().indexOf('<tbody>');
                if (tbody_pos != -1) begin = begin.substr(0, tbody_pos);
                div_o.innerHTML = begin + '<tbody>' + html + '</tbody>';

                // refresh new ids
                this.ids = js_ids;
                var opened_ids = this.getOpenedIds(), k;
                // save opened ajax ids
                for (k in this.ids) {
                    if (in_array(k, opened_ids)) this.expandedAjaxIds[k] = true;
                }
                this.openNodes();
            };
            TableTree.prototype.expand = function (id, open) {
                if (!this.ids[id]) return;
                var o = document.getElementById('toggle_' + id), open_next;
                if (o) {
                    o.innerHTML = '-';
                    open_next = true;
                } else open_next = false;
                this.saveOpenedId(id);
                var children = this.ids[id].children, so = children.length, i = 0;
                if (so === 0 && isset(open) && open === true) {
                    this.expandAjax(id);
                }

                for (; i < so; i++) {
                    o = document.getElementById('id_' + children[i]);
                    if (o) o.style.display = '';
                    if (in_array(children[i], this.collapsed)) {
                        this.expand(children[i], open_next);
                    }
                }
            };
            TableTree.prototype.collapse = function (id, inner_children) {
                if (document.getElementById('toggle_' + id)) {
                    document.getElementById('toggle_' + id).innerHTML = '+';
                }
                this.deleteOpenedId(id);

                var children = this.ids[id].children, SO = children.length, ch_id, ch_o, toggle_o, prev_state_visible;

                if (!isset(inner_children)) this.deleteCollapsedOpenedId(id);
                for (var i = 0; i < SO; i++) {
                    ch_id = children[i];
                    ch_o = document.getElementById('id_' + ch_id);
                    prev_state_visible = (ch_o.style.display != 'none');
                    ch_o.style.display = 'none';
                    if (prev_state_visible) {
                        toggle_o = document.getElementById('toggle_' + ch_id);
                        if (toggle_o && toggle_o.innerHTML === '-') this.saveCollapsedOpenedId(ch_id);
                        this.collapse(ch_id, true);
                    }
                }
            };
            TableTree.prototype.fixArray = function (ids) {
                if (ids == null) {
                    return [];
                }
                ids = ids.split(',');
                // fix array
                var tmp = [], SO = ids.length, i = 0;
                for (; i < SO; i++) {
                    if (ids[i]) {
                        tmp.push(ids[i]);
                    }
                }
                return tmp;
            };
            TableTree.prototype.getOpenedIds = function () {
                var ids = this.storage.get(this.storage_key);
                return this.fixArray(ids);
            };
            TableTree.prototype.saveCollapsedOpenedId = function (id) {
                if (!this.save_inner) return false;
                if (!in_array(id, this.collapsed)) {
                    this.collapsed.push(id);
                    this.storage.set(this.storage_key_collapsed, this.collapsed);
                }
            };
            TableTree.prototype.deleteCollapsedOpenedId = function (id) {
                if (!this.save_inner) return false;
                if (in_array(id, this.collapsed)) {
                    this.collapsed = array_kick_by_value(this.collapsed, id);
                    this.storage.set(this.storage_key_collapsed, this.collapsed);
                }
            };
            TableTree.prototype.getCollapsedOpenedId = function () {
                var ids = this.storage.get(this.storage_key_collapsed);
                return this.fixArray(ids);
            };
            TableTree.prototype.saveOpenedId = function (id) {
                var ids = this.getOpenedIds();
                if (in_array(id, ids)) return;
                ids.push(id);
                this.storage.set(this.storage_key, ids);
            };
            TableTree.prototype.deleteOpenedId = function (id) {
                var ids = this.getOpenedIds();
                if (in_array(id, ids)) {
                    this.storage.set(this.storage_key, array_kick_by_value(ids, id));
                }
            };
            TableTree.prototype.openNodes = function () {
                var ids = this.getOpenedIds(), so = ids.length, i = 0;
                for (; i < so; i++) this.expand(ids[i]);
            };
            TableTree.prototype.openAjaxNodes = function () {
                var this_o = this;
                jQuery.ajax({
                    type: 'GET',
                    url: this.ajax_path + '&nomenu=1&ajax_tree=1&ajax_tree_opener=1&ajax_tree_ids=0,' + this.getOpenedIds().join(','),
                    success: function (data) {
                        this_o.expandAjaxOpenedIds(data);
                    }
                });
            }
        </script><?php

        self::$js_loaded = true;
    }

    /**
     * @param array $data
     * @throws \InvalidArgumentException
     */
    public function init(array &$data)
    {
        $default_lvl = 0;

        foreach ($data as $row_data) {
            $id = $this->getCellData($row_data, $this->id_column);
            if ($id === '') {
                throw new InvalidArgumentException('ID either is empty or was not found in result set');
            }
            $pid = $this->getCellData($row_data, $this->pid_column);
            if ($pid === '') {
                $pid = 0;
            }

            if (isset($this->child_ids[$id])) {
                $this->child_ids[$pid]['children'][] = $id;
            } else {
                $this->child_ids[$id] = [
                    'level' => $default_lvl,
                    'children' => []
                ];
                if (isset($this->child_ids[$pid])) {
                    $this->child_ids[$pid]['children'][] = $id;
                } else {
                    $this->child_ids[$pid] = [
                        'level' => $default_lvl,
                        'children' => [$id]
                    ];
                }
            }
        }

        foreach ($this->child_ids as $id => $v) {
            $this->child_ids[$id]['level'] = $this->getRowLevel((string)$id);
        }

        if ($this->child_ids) {
            $min_lvl_id = $this->getMinLvlId();

            $arr = (array)$this->child_ids[$min_lvl_id]['children'];
            $new_data = [];

            foreach ($arr as $id) {
                $this->getChildren($id, $new_data, $data);
            }
            $data = $new_data;
        }
    }

    /**
     * @param string $id
     * @param int $lvl
     *
     * @return int
     */
    private function getRowLevel(string $id, int $lvl = 0)
    {
        reset($this->child_ids);

        foreach ($this->child_ids as $pid => $data) {
            if (!\in_array($id, $data['children'], true)) {
                continue;
            }
            if (!$pid) {
                return $lvl;
            }
            $lvl += $this->getRowLevel((string)$pid, $lvl++);
            break;
        }

        if ($lvl > $this->_max_level) {
            $this->_max_level = $lvl;
        }

        return $lvl;
    }

    /**
     * @return int
     */
    private function getMinLvlId(): int
    {
        if (isset($this->child_ids[0])) {
            return 0;
        }

        $min_id = 0;
        $min_lvl = 99999999;

        foreach ($this->child_ids as $id => $data) {
            if ($min_lvl > $data['level']) {
                $min_lvl = $data['level'];
                $min_id = $id;
            }
        }

        return $min_id;
    }

    /**
     * @param string $id
     * @param array $new_data
     * @param array $data
     */
    private function getChildren(string $id, array &$new_data, array &$data)
    {
        if (!isset($data[$id], $this->child_ids[$id])) {
            return;
        }

        $new_data[$id] = $data[$id];

        if ($this->child_ids[$id]['children']) {
            foreach ($this->child_ids[$id]['children'] as $cid) {
                $this->getChildren($cid, $new_data, $data);
            }
        }
    }

    /**
     * @return array
     */
    public function getOrdersData(): array
    {
        $res = [];

        foreach ($this->child_ids as $id => $data) {
            $res[$id] = ['current' => 0, 'total' => \count($data['children'])];
        }

        return $res;
    }

    /**
     * @return array
     */
    public function getChildIds(): array
    {
        return $this->child_ids;
    }

    /**
     * @return int
     */
    public function getMaxLevel(): int
    {
        return $this->_max_level;
    }

    /**
     * @param int $row
     * @param array $row_data
     * @param Linker $linker
     *
     * @return string
     * @throws \RuntimeException
     */
    public function getView(int $row, array $row_data, Linker $linker): string
    {
        $cell = '';
        $id = $this->getCellData($row_data, $this->id_column);
        $for_show = $this->getCellData($row_data, $this->show_key);

        if ($this->href) {
            $for_show = '<a class="nounderline" href="' . $this->getParsedHref($row_data, new Linker((\defined('P') ? P : ''), $_GET['do'] ?? '')) . '">' . $for_show . '</a>';
        }

        if ($this->ajax) {
            // Check that we have child data
            if (!isset($row_data['ajax_tree_leaf'])) {
                throw new RuntimeException('If you are using ColumnTree with ajax and table contains data array not sql, please add following column to select:
                    ",IF ((SELECT `' . $this->id_column . '` FROM `YOUR_TABLE` WHERE `' . $this->pid_column . '` = `YOUR_TABLE_ALIAS`.`' . $this->id_column . '` LIMIT 1) IS NULL, 1, 0) AS `ajax_tree_leaf`"');
            }

            $cell = '<div style="padding-left: '
                . (($this->getRowLevel($id) + (isset($_GET['ajax_tree_node_lvl']) ? (int)$_GET['ajax_tree_node_lvl'] : 0)) * $this->child_padding)
                . 'px">'
                . (!$row_data['ajax_tree_leaf']
                    ? '<a href="" class="nounderline" style="font-family: monospace;" id="toggle_'
                        . $id
                        . '" onclick="'
                        . $this->js_object_name
                        . '.toggleAjax(\'' . $id . '\'); return false;">+</a>&nbsp;'
                    : '<span style="visibility: hidden; style="font-family: monospace;">+</span>&nbsp;'
                )
                . $for_show . '</div>';
        } else {
            $cell = '<div style="padding-left: '
                . ($this->getRowLevel($id) * $this->child_padding)
                . 'px">'
                . (isset($this->child_ids[$id]) && $this->child_ids[$id]['children']
                    ? '<a href="" class="nounderline" style="font-family: monospace;" id="toggle_'
                        . $id
                        . '" onclick="'
                        . $this->js_object_name
                        . '.toggle(\'' . $id . '\'); return false;">+</a>&nbsp;'
                    : '<span style="visibility: hidden; style="font-family: monospace;">+</span>&nbsp;'
                ) . $for_show . '</div>';
        }

        return $this->getCellView($cell, $row_data);
    }
}
