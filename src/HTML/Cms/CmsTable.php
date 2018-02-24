<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms;

use ArgumentCountError;
use InvalidArgumentException;
use LogicException;
use PDO;
use TMCms\DB\SQL;
use TMCms\DB\SQLParser\SQLParser;
use TMCms\DB\SQLPrepared;
use TMCms\HTML\Cms\Column\ColumnAutoNumber;
use TMCms\HTML\Cms\Column\ColumnCheckbox;
use TMCms\HTML\Cms\Column\ColumnInput;
use TMCms\HTML\Cms\Column\ColumnOrder;
use TMCms\HTML\Cms\Column\ColumnTree;
use TMCms\HTML\Cms\Element\CmsButton;
use TMCms\HTML\Cms\Element\CmsRow;
use TMCms\HTML\Cms\Element\CmsSelect;
use TMCms\HTML\Cms\Filter\Hidden;
use TMCms\Orm\EntityRepository;
use TMCms\Templates\PageHead;

\defined('INC') or exit;

/**
 * Class CmsTable
 * @package TMCms\HTML\Cms
 */
class CmsTable
{
    const TR_ID_PREFIX = 'id_';
    protected $enabled_save_into_file = false;
    protected $multiple_actions = [];
    protected $width = '100%';
    protected $data = [];
    /**
     * @var FilterForm
     */
    protected $filter_form;
    private $id = '';
    private $columns_groups = [];
    private $grouped_columns = [];
    private $columns = [];
    private $dragable = false;
    private $tree = false;
    private $tree_node_id = '';
    private $append_empty_row = false;
    private $action = '';
    private $show_add_row = false;
    private $js = [];
    private $show_sql = false;
    /**
     * @var Linker
     */
    private $linker;
    private $show_pager = true;
    /**
     * @var TablePager
     */
    private $pager;
    private $per_page = 25;
    private $sql_data = false;
    private $sql_params = [];
    private $row_id_column = 'id';

    private $spec_row_values = false;
    private $context_menu_items = [];

    private $callback = false;
    private $callback_params = [];
    /**
     *
     * @var HtmlAttributes
     */
    private $spec_row_attr = false;
    private $editable_table = false;
    private $heading_title = '';
    private $heading_icon = '';
    private $default_input_rows = 0;

    /**
     * @param string $id
     */
    public function  __construct(string $id = '')
    {
        $this->id = $id;
        $this->linker = new Linker(\defined('P') ? P : '', $_GET['do'] ?? '');

        if (isset($_GET['per_page']) && abs((int)$_GET['per_page'])) {
            $per_page = abs((int)$_GET['per_page']);
        } else {
            $per_page = $this->per_page;
        }

        $this->pager = new TablePager(0, 0, $per_page);
    }

    /**
     *
     * @param string $id
     *
     * @return $this
     */
    public static function getInstance(string $id = '')
    {
        return new self($id);
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setHeadingTitle(string $title) {
        $this->heading_title = $title;

        return $this;
    }

    /**
     * @param string $icon
     *
     * @return $this
     */
    public function setHeadingIcon(string $icon) {
        $this->heading_icon = $icon;

        return $this;
    }

    /**
     * @param bool $flag
     *
     * @return $this
     */
    public function showSql(bool$flag)
    {
        $this->show_sql = $flag;

        return $this;
    }

    /**
     * @return int
     */
    public function getPerPage(): int
    {
        return $this->pager->getPerPage();
    }

    /**
     * @param int $entries
     *
     * @return $this
     */
    public function setPerPage(int $entries)
    {
        $this->pager->setPerPage($entries);

        return $this;
    }

    /**
     * @param string $action
     *
     * @return $this
     */
    public function setAction(string $action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @param bool $flag
     *
     * @return $this
     */
    public function showAddRow(bool $flag)
    {
        $this->show_add_row = $flag;

        return $this;
    }

    /**
     * @param int $rows
     *
     * @return $this
     */
    public function setDefaultRows(int $rows)
    {
        $this->default_input_rows = $rows;

        return $this;
    }

    /**
     * If ColumnInput is used
     *
     * @param mixed CmsButton $submitButton or String button title
     *
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setButtonSubmit($submitButton)
    {
        if (\is_string($submitButton)) {
            $this->submitButton = CmsButton::getInstance($submitButton);
        } elseif ($submitButton instanceof CmsButton) {
            $this->submitButton = $submitButton;
        } else {
            throw new InvalidArgumentException('setButtonSubmit parameter type must be String or CmsButton.');
        }

        return $this;
    }

    /**
     * set row data key for tr id
     *
     * @param string $column
     *
     * @return $this
     */
    public function setRowIdColumn(string $column)
    {
        $this->row_id_column = $column;

        return $this;
    }

    /**
     * set attributes for passed rows (or all rows)
     * @param mixed $values array(1,2,3,4) when rowIdColumn value is in this set, attr will be used
     *        bool - true for each row
     * @param HtmlAttributes $attr - tr attributes
     *
     * @return $this
     */
    public function setRowAttributes($values, HtmlAttributes $attr)
    {
        $this->spec_row_values = $values;
        $this->spec_row_attr = $attr;

        return $this;
    }

    /**
     * @param int $total_rows
     * @param int $per_page default 20
     *
     * @return CmsTable
     */
    public function showPager(int $page = 0, int $total_rows = 0, int $per_page = 0)
    {
        $this->show_pager = true;
        $per_page = $per_page ?? $this->per_page;

        if (isset($_GET['per_page']) && ctype_digit((string)$_GET['per_page'])) {
            $per_page = (int)$_GET['per_page'];
        }

        $this->pager = new TablePager($page, $total_rows, $per_page);

        return $this;
    }

    /**
     * @param mixed
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function addData($data) {
        if (\is_string($data)) {
            $this->addDataSql($data);
        } elseif (\is_array($data)) {
            $this->addDataArray($data);
        } elseif (\is_object($data)) {
            $this->addDataObjectCollection($data);
        } elseif (\is_resource($data)) {
            $this->addDataQueryResource($data);
        } else {
            throw new InvalidArgumentException('Unsupported data type.');
        }
        return $this;
    }

    /**
     * @param $data
     *
     * @return $this
     */
    public function addDataSql(string $data)
    {
        $this->sql_data = true;
        $this->sql_params = [];

        $args_count = \func_num_args();
        $args = \func_get_args();

        for ($i = 1; $i < $args_count; $i++) {
            $this->sql_params[] = $args[$i];
        }

        $this->data = $data;

        return $this;
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public function addDataArray(array $data)
    {
        $this->data = $data;
        $this->pager->setTotalRows(\count($this->data));

        return $this;
    }

    /**
     * @param EntityRepository $collection
     *
     * @return $this
     */
    public function addDataObjectCollection($collection)
    {
        return $this->addDataSql($collection->getSelectSql());
    }

    /**
     * @param $data
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function addDataQueryResource($data)
    {
        if (!\is_resource($data)) {
            throw new InvalidArgumentException('Data must be a resource.');
        }

        $this->data = q_assoc_iterator($data);
        $this->pager->setTotalRows(\count($this->data));

        return $this;
    }

    /**
     * function example is lambda
        function($data) {
            foreach ($data as & $v) {
                // modify $v somehow here
            }
            return $data;
        }
     * @param $function
     * @param array $params
     *
     * @return $this
     */
    public function setCallbackFunction($function, array $params = [])
    {
        $this->callback = $function;
        $this->callback_params = $params;

        return $this;
    }

    /**
     * @param string $group_title
     *
     * @return $this
     * @throws ArgumentCountError
     */
    public function addColumnsGroup(string $group_title)
    {
        $args_count = \func_num_args();
        $args = \func_get_args();

        if ($args_count === 1) {
            throw new ArgumentCountError('Column group must contain at least one column.');
        }

        $columns = [];

        for ($i = 1; $i < $args_count; $i++) {
            $this->addColumn($args[$i]);
            /** @var Column $arg */
            $arg = $args[$i];
            $columns[] = $arg->getKey();
        }

        $this->grouped_columns[] = $columns;
        $this->columns_groups[] = $group_title;

        return $this;
    }

    /**
     * @param Column $column
     *
     * @return $this
     * @throws LogicException
     */
    public function addColumn(Column $column)
    {
        $this->columns[] = $column;

        if ($column instanceof ColumnInput) {
            $this->append_empty_row = true;
            $this->setWidth('100%');
        } elseif ($column instanceof ColumnOrder && $column->isDragable()) {
            $this->dragable = true;
        } elseif ($column instanceof ColumnTree && !$this->tree) {
            $this->tree_node_id = \count($this->columns) - 1;
            $this->tree = true;
        } elseif ($column instanceof ColumnTree && $this->tree) {
            throw new LogicException('May be only one ColumnTree');
        }

        return $this;
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
     * @param string $string
     *
     * @return $this
     */
    public function setPagerPrefixString($string)
    {
        $this->pager->setPrefixString($string);

        return $this;
    }

    /**
     * context menu for table rows, with dynamic parameters
     * @param array $menu array( array('title' => 'TEST', 'confirm' => true // not obligate //, 'popup' => true // not obligate //, 'href' => 'http://www.google.com', 'js' => 'alert();') ,  array(..) , ...)
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function addContextMenu(array $menu)
    {
        foreach ($menu as &$item) {
            if (!\is_array($item) || !isset($item['title'])) {
                throw new InvalidArgumentException('Invalid structure. Example: [ [\'title\' => \'TEST\', \'href\' => \'http://www.google.com\', \'popup\' => \'true\', \'confirm\' => \'true\'] ,  [..] , ...]');
            }
        }
        unset($item);

        foreach ($menu as $menu_key => &$item) {
            if (!isset($item['js'])) {
                $item['js'] = '';
            }
            if (!isset($item['href'])) {
                $item['href'] = '';
            }
            if (!isset($item['popup'])) {
                $item['popup'] = false;
            }

            if (!isset($item['confirm'])) {
                $item['confirm'] = false;
            }

            foreach ($item as $item_key => $item_value) {
                $item[$item_key] = str_replace(["'", '"'], ["\'", ''], $item[$item_key]);
            }

            // Bool to int for javascript
            $item['confirm'] = (int)$item['confirm'];
            $item['popup'] = (int)$item['popup'];

            $this->context_menu_items[] = $item;

        }

        return $this;
    }

    /**
     * @param FilterForm $form
     *
     * @return $this
     */
    public function attachFilterForm(FilterForm $form)
    {
        $this->filter_form = $form;

        return $this;
    }

    /**
     * @return FilterForm
     */
    public function getFilterForm(): FilterForm
    {
        return $this->filter_form;
    }

    /**
     * @param string $tbl
     * @param string $tbl_alias
     * @param string $id_column
     * @param string $pid_column
     * @return string
     */
    public function getTreeAjaxSelect(string $tbl, string $tbl_alias, string $id_column, string $pid_column): string
    {
        return ',IF ((SELECT `id` FROM `' . $tbl . '` WHERE `' . $pid_column . '` = `' . $tbl_alias . '`.`' . $id_column . '` LIMIT 1) IS NULL, 1, 0) AS `ajax_tree_leaf`';
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if (!$this->id) {
            $this->id = 'autoTable' . md5(serialize($this->columns) . '_' . (\defined('P') ? P : '') . '_' . ($_GET['do'] ?? ''));
        }

        // Prepare Column for MultiActions
        if ($this->multiple_actions) {
            $options = ['' => ''];
            ?>
            <script>
                multi_actions.registered_action['<?= $this->id ?>'] = {};
                $(function(){
                    $('#select_all').click(function(e) {
                        var selects = $('[data-column_key=multiple_action] form input[type=checkbox][data-table-id=<?= $this->id ?>]');
                        if (selects.filter(':checked').length > 0 && selects.filter(':checked').length < selects.length) {
                            selects.filter(':checked').click();
                        } else {
                            selects.click();
                        }
                    });
                })
            </script>
            <?php
            foreach ($this->multiple_actions as $action) {
                if (!isset($action['action'])) {
                    $action['action'] = $action['link'];
                }
                if (!isset($action['name'])) {
                    $action['name'] = $action['action'];
                }

                $options[$action['action']] = $action['name'];
                ?>
                <script>
                    multi_actions.registered_action['<?= $this->id ?>']['<?= $action['action'] ?>'] = {
                        'link': '<?= $action['link'] ?>',
                        'confirm': <?= isset($action['confirm']) && $action['confirm'] ? 'true' : 'false' ?>
                    };
                </script>
                <?php
            }

            $this->addColumn(
                    ColumnCheckbox::getInstance('multiple_action')
                        ->setOnclick('javascript:void(0)')
                        ->addDataAttribute('table-id', $this->id)
                        ->setTitle((string)
                                CmsSelect::getInstance('multiple_action')
                                    ->setOptions($options)
                                    ->disableCustomStyled()
                                    ->setOnchange('multi_actions.submit(this)')
                        )
            );
        }

        /* @var $column Column */
        foreach ($this->columns as $column) {
            // Set available data from parent
            $column->setTableId($this->id);

            // Check that we have ID column for trees
            if ($this->row_id_column !== false) {
                if ($column instanceof ColumnTree && $column->getIdColumn() !== $this->row_id_column) {
                    throw new LogicException('It is not possible to use ColumnTree. Row id columns are different');
                }
            }
        }
        $this->prepareForOutput();

        if ($this->tree && isset($_GET['ajax_tree'])) {
            ob_clean();
            if (isset($_GET['ajax_tree_opener'])) {
                $html = $this->outputAjaxRows(); // !!! MUST BE BEFORE getTreeColumnChildrenIds
                echo json_encode($this->getTreeColumnChildrenIds()), "\n", $html;
            } else {
                echo $this->outputAjaxRows();
            }

            exit;
        }

        ob_start();

        if ($this->filter_form) {
            echo $this->filter_form;
        }

        ?>
        <div id="htmlgen_table_container">
            <div class="portlet box">
                <?php if ($this->heading_title): ?>
                    <div class="portlet-title">
                        <div class="caption">
                            <?php if ($this->heading_icon): ?>
                                <i class="fa fa-<?= $this->heading_icon ?>"></i>
                            <?php endif; ?>
                            <?= $this->heading_title ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="portlet-body">
                    <?php if (!empty($this->multiple_actions)): ?>
                        <div class="clearfix">
                            <div class="pull-right">
                                <button class="btn btn-success btn-xs" id="select_all"><?= __('Select/Deselect all') ?></button>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="table-scrollable">
                        <table class="table table-striped table-bordered table-hover" id="<?= $this->id ?>">
                            <?= $this->getHeadersView() ?>
                            <?= $this->getDataView() ?>
                            <?= $this->data ? $this->getTotalView() : '' ?>
                        </table>
                    </div>
                </div>
                <?php if ($this->show_pager) {
                    echo $this->pager;
                } ?>
            </div>
        </div>
        <?php
        if ($this->js) {
            echo '<script>', implode(';', $this->js), '</script>';
        }
        if ($this->dragable) {
            ColumnOrder::getDragAndDropJS($this->id);
        }
        $col_input_exist = 0;
        foreach ($this->columns as $column) {
            /* @var $column Column */
            echo $column->getJs($this->id);
            if (!$col_input_exist && $column instanceof ColumnInput) {
                $col_input_exist = 1;
            }
        }
        if ($col_input_exist) {
            if ($this->default_input_rows): ?>
                <script>
                    <?php for ($i = 0; $i < $this->default_input_rows; $i++) : ?>
                    table_form<?=$this->id?>.add_row();
                    <?php endfor; ?>
                </script>
                <?php
            endif;
            // Special for ColumnInput, using in Cmsform
            if ($this->show_add_row): ?>
                <div style="text-align: left; width: <?= $this->width ?>; margin-top: 10px">
                    <var class="btn btn-info icon-plus" onclick="table_form<?= $this->id ?>.add_row();"></var>
                </div>
            <?php endif;
        }

        if (isset($this->submitButton)) {
            $table = ob_get_clean();
            ob_start();
            echo CmsForm::getInstance()
                ->setAction($this->action)
                ->setButtonSubmit('Save')
                ->addField('', CmsRow::getInstance('')->setValue($table));
        }

        return ob_get_clean();
    }

    private function prepareForOutput()
    {
        if ($this->tree) {
            $this->disablePager();
        }

        if ($this->sql_data) {
            // Inserting sum and avg values into table and checking order by
            if (!$this->tree) {
                $sum_avg_data = [];
                $default_order_by_index = false;

                /** @var Column $column */
                foreach ($this->columns as $i => $column) {
                    $order_by = $column->getOrderBy();
                    if ($order_by['applied']) {
                        $order_by_sql = $order_by['by'] . $order_by['direction'];
                    }

                    if ($column->getOrderByDefault()) {
                        if ($default_order_by_index !== false) {
                            throw new LogicException('Only one column can be default for ORDER BY. Already set for "' . $this->columns[$default_order_by_index]->getKey() . '". You try to set also for "' . $column->getKey() . '"');
                        }
                        $default_order_by_index = $i;
                    }

                    $sum_total = $column->getSumTotal();
                    $avg_total = $column->getAvgTotal();
                    $sum_filtered = $column->getSumFiltered();
                    $avg_filtered = $column->getAvgFiltered();

                    if (!$sum_total && !$avg_total && !$sum_filtered && !$avg_filtered) {
                        continue;
                    }

                    $sum_avg_data[$i] = [
                        'key' => $column->getKey(),
                        'sum_total' => $sum_total,
                        'avg_total' => $avg_total,
                        'sum_filtered' => $sum_filtered,
                        'avg_filtered' => $avg_filtered
                    ];
                }

                if (isset($order_by_sql) && $order_by_sql && $default_order_by_index !== false) {
                    $this->columns[$default_order_by_index]->removeOrderByDefault();
                }
            }

            $sql_parser = new SQLParser($this->data);
            if (isset($order_by_sql)) {
                $sql_parser->setPart('order_conditions', $order_by_sql);
            }

            if ($this->filter_form) {
                foreach ($this->filter_form->getFields() as $field) {
                    /* @var $field CmsFormElement */
                    $element = $field->getElement();

                    /** @var Hidden $element */
                    if ((!($element instanceof Hidden) && !$element->isEmpty()) && (!$element->isIgnoreFilterInWhereSqlEnabled())) {
                        switch ($element->getActAs()) {
                            case 'l':
                            case 'like':

                                $value = 'LIKE %' . $element->getActAs();

                                break;

                            case 'ai':
                            case 'aui':
                            case 'as':
                            case 'af':
                            case 'auf':
                            case 'arrayint':
                            case 'arrayuint':
                            case 'arraystring':
                            case 'arrayfloat':
                            case 'arrayufloat':

                                $value = 'IN (%' . $element->getActAs() . ')';

                                break;

                            default:

                                $value = '= %' . $element->getActAs();

                                break;

                        }

                        if (!$element->getFilter()->getColumn()) {
                            $element->setColumn($element->getName()); // NO NEED FOR PARAM !! This will break filtering in forms
                        }

                        $columns = $element->getFilter()->getFormattedColumn();
                        $node = $sql_parser->getWhere()->addAnd(array_shift($columns) . ' ' . $value);
                        $this->sql_params[] = $element->getFilterValue();

                        foreach ($columns as &$column) {
                            $node = $sql_parser->getWhere()->addOr($column . ' ' . $value, $node);
                            $this->sql_params[] = $element->getFilterValue();
                        }
                        unset($column);
                    }
                }
            }

            if ($this->tree) {
                $ajax_tree = $pid_column = $id_column = false;

                foreach ($this->columns as $column) {
                    if ($column instanceof ColumnTree) {
                        /** @var ColumnTree $column */
                        $pid_column = $column->getPidColumn();
                        $id_column = $column->getIdColumn();
                        $ajax_tree = $column->isAjax();
                        break;
                    }
                }

                // FOR AJAX TREE ONLY
                if ($ajax_tree && $pid_column && $id_column) {
                    $data = $sql_parser->getData();
                    $tbl = $data['table_references'][0];

                    $tbl_alias_start_pos = stripos($tbl, ' AS ');

                    if ($tbl_alias_start_pos !== false) {
                        $tbl_without_alias = trim(substr($tbl, 0, $tbl_alias_start_pos));
                        $tbl_alias = trim(substr($tbl, $tbl_alias_start_pos + 4));
                    } else {
                        $tbl_without_alias = $tbl;
                        $tbl_alias = false;
                    }

                    $ids = $this->getTreeAjaxPidIds();
                    $select_leaf = ',IF ((SELECT `' . $id_column . '` FROM ' . $tbl_without_alias . ' WHERE `' . $pid_column . '` = ' . ($tbl_alias ? $tbl_alias . '.' : null) . '`' . $id_column . '` LIMIT 1) IS NULL, 1, 0) AS `ajax_tree_leaf`';
                    $sql_parser->setPart('select_expr', $data['select_expr'][0] . $select_leaf);

                    $sql_parser->getWhere()->addAnd(($tbl_alias ? $tbl_alias . '.' : null) . '`' . $pid_column . '` IN (' . $ids . ')');
                }
            }

            if ($this->show_pager) {
                $page = $this->pager->getPage();
                $per_page = $this->pager->getPerPage();
                $sql_parser->getLimit()->setOffset($page * $per_page)->setLimit($per_page);
                $sql_parser->addFlag('SQL_CALC_FOUND_ROWS');

                if ($this->show_sql) {
                    dump(SQLPrepared::getInstance($sql_parser->toSQL(), $this->sql_params)->execute(), 'SQL');
                }

                $this->data = [];
                $qh = q(SQLPrepared::getInstance($sql_parser->toSQL(), $this->sql_params)->execute());

                while ($q = $qh->fetch(PDO::FETCH_ASSOC)) {
                    if (isset($q['id'])) {
                        $this->data[$q['id']] = $q;
                    } else {
                        $this->data[] = $q;
                    }
                }
                $this->pager->setTotalRows(SQL::selectFoundRows());
            } else {
                if ($this->show_sql) {
                    dump(SQLPrepared::getInstance($sql_parser->toSQL(), $this->sql_params)->execute(), 'SQL');
                }

                $this->data = [];
                $qh = q(SQLPrepared::getInstance($sql_parser->toSQL(), $this->sql_params)->execute());

                while ($q = $qh->fetch(PDO::FETCH_ASSOC)) {
                    if (isset($q['id'])) {
                        $this->data[$q['id']] = $q;
                    } else {
                        $this->data[] = $q;
                    }
                }

                $this->pager->setTotalRows(SQL::selectFoundRows());
            }

            if (isset($sum_avg_data) && $sum_avg_data) {
                // Calculating total sum and/or avg
                foreach ($sum_avg_data as $i => &$v) {
                    $sql_k = '`' . str_replace('.', '`.`', $v['key']) . '`';
                    if ($v['sum_total'] || $v['avg_total']) {
                        $total_sql_parser = clone $sql_parser;
                        $total_sql_parser->removeAllFlags();
                        $total_sql_parser->removeByWord('LIMIT');
                        $total_sql_parser->removeByWord('GROUP BY');
                        $total_sql_parser->removeByWord('ORDER BY');
                        $total_sql_parser->removeByWord('HAVING');
                        $select_expr = '';

                        if ($v['sum_total']) {
                            $select_expr .= 'SUM(' . (is_string($v['sum_total']) ? $v['sum_total'] : $sql_k) . ') AS `sum`';
                            if ($v['avg_total']) {
                                $select_expr .= ', ';
                            }
                        }

                        if ($v['avg_total']) {
                            $select_expr .= 'AVG(' . $sql_k . ') AS `avg`';
                        }
                        $total_sql_parser->setPart('select_expr', $select_expr, 0);
                        $total_res = q_assoc_row(SQLPrepared::getInstance($total_sql_parser->toSQL(), $this->sql_params)->execute());
                        if ($v['sum_total']) {
                            $this->columns[$i]->setSumTotal($total_res['sum']);
                        }
                        if ($v['avg_total']) {
                            $this->columns[$i]->setAvgTotal($total_res['avg']);
                        }
                    }

                    if ($v['sum_filtered'] || $v['avg_filtered']) {
                        $total_sql_parser = clone $sql_parser;
                        $total_sql_parser->removeAllFlags();
                        $total_sql_parser->removeByWord('LIMIT');
                        $total_sql_parser->removeByWord('GROUP BY');
                        $total_sql_parser->removeByWord('ORDER BY');
                        $total_sql_parser->removeByWord('HAVING');
                        $select_expr = '';
                        if ($v['sum_filtered']) {
                            $select_expr .= 'SUM(' . $sql_k . ') AS `sum`';
                            if ($v['avg_filtered']) {
                                $select_expr .= ', ';
                            }
                        }
                        if ($v['avg_filtered']) {
                            $select_expr .= 'AVG(' . $sql_k . ') AS `avg`';
                        }
                        $total_sql_parser->setPart('select_expr', $select_expr, 0);
                        $total_res = q_assoc_row(SQLPrepared::getInstance($total_sql_parser->toSQL(), $this->sql_params)->execute());
                        if ($v['sum_filtered']) {
                            $this->columns[$i]->setSumFiltered($total_res['sum']);
                        }
                        if ($v['avg_filtered']) {
                            $this->columns[$i]->setAvgFiltered($total_res['avg']);
                        }
                    }
                }
                unset($v);
            }
            unset($sum_avg_data);
        } else {
            if ($this->show_pager) {
                $this->pager->setTotalRows(\count($this->data));

                $page = $this->pager->getPage();
                $per_page = $this->pager->getPerPage();

                $this->data = \array_slice($this->data, $page * $per_page, $per_page, true);
            }
        }

        $magic_data_column_id = false;
        foreach ($this->columns as $column) {
            if ($column instanceof ColumnTree) {
                $magic_data_column_id = $column->getIdColumn();
            }
            if ($column instanceof ColumnAutoNumber) {
                /** @var ColumnAutoNumber $column */
                $column->setRowOffset($this->pager->getPage() * $this->pager->getPerPage());
            } elseif ($column instanceof ColumnOrder) {
                /** @var ColumnOrder $column */
                $column->rowCount($this->pager->getTotalRows());
                $column->rowOffset($this->pager->getPage() * $this->pager->getPerPage());
            }
        }

        // ONLY FOR ColumnTree for init method - where data key = row ID needed
        if ($magic_data_column_id && !$this->sql_data) {
            $new_data = [];

            foreach ($this->data as $row_data) {
                $new_data[$row_data[$magic_data_column_id]] = $row_data;
            }

            $this->data = $new_data;
        }

        if ($this->callback) {
            $function = $this->callback;

            if (\is_string($function) && substr_count($function, '::') === 1) {
                $this->data = \call_user_func(explode('::', $function), $this->data, $this->callback_params);
            } else {
                $this->data = $function($this->data, $this->callback_params);
            }
        }
    }

    /**
     * @return $this
     */
    public function disablePager()
    {
        $this->show_pager = false;

        return $this;
    }

    /**
     * @return string
     */
    public function getTreeAjaxPidIds(): string
    {
        if (isset($_GET['ajax_tree']) && isset($_GET['ajax_tree_ids']) && preg_match('/^[0-9,]+$/', $_GET['ajax_tree_ids'])) {
            $ids = array_unique(array_diff(explode(',', $_GET['ajax_tree_ids']), array('')));
            if ($ids) {
                $ids = implode(',', $ids);
            } else {
                $ids = '"0"';
            }
        } else {
            $ids = '"0"';
        }

        return $ids;
    }

    /**
     * @return string
     */
    private function outputAjaxRows(): string
    {
        return $this->getDataView();
    }

    /**
     * @return string
     * @throws \InvalidArgumentException
     */
    private function getDataView(): string
    {
        $i = 1;

        ob_start();

        if ($this->tree) {
            $orders_data = [];
        }

        foreach ($this->columns as $column) {
            if ($this->tree && $column instanceof ColumnTree) {
                $column->init($this->data);
                $orders_data = $column->getOrdersData();
            }
        }


        if (!$this->tree || ($this->columns[$this->tree_node_id] instanceof ColumnTree && strpos($this->columns[$this->tree_node_id]->getAjaxPath(), '&ajax_tree_node_lvl=') === false)) {
            ?><tbody><?php
            $tbody = true;
        } else {
            $tbody = false;
        }

        // Special for ColumnInput - we have do draw empty row that will be copied using Js
        if ($this->append_empty_row) {
            $tmp_remove = false;
            if (!$this->data) {
                $this->data = [[]]; // For first empty row
                $tmp_remove = true;
            }

            $tmp = \array_slice($this->data, 0, 1);

            foreach ($tmp as &$v) {
                $v = '';
            }
            unset($v);

            $tmp['id'] = '-1';
            $this->data[] = $tmp;
            if ($tmp_remove) {
                array_shift($this->data);
            }
        }

        $context_menu_js = [];
        foreach ($this->data as $row_data) {
            $styles = [];

            foreach ($this->columns as $column) {
                /* @var $column Column */
                if ($this->tree && $column instanceof ColumnOrder) {
                    /** @var ColumnOrder $column */
                    $order_data =& $orders_data[$column->getCellData($row_data, 'pid')];
                    $column->setCurrentOffset($order_data['current']);
                    $column->rowCount($order_data['total']);
                    $order_data['current']++;
                }

                $style = $column->getRowStyle($row_data);
                if ($style) {
                    $styles[] = $style;
                }
            }

            if ((isset($row_data[$this->row_id_column]) && \is_array($this->spec_row_values) && $this->spec_row_values && in_array($row_data[$this->row_id_column], $this->spec_row_values)) || (\is_bool($this->spec_row_values) && $this->spec_row_values == true)
            ) {
                $cur_row_attr = clone $this->spec_row_attr;
            } else {
                $cur_row_attr = new HtmlAttributes();
            }

            if (isset($row_data[$this->row_id_column])) {
                $cur_row_attr->id(self::TR_ID_PREFIX . $row_data[$this->row_id_column]); // Method call must be ->id()
                // Highlight row with ID in GET
                if (isset($_GET['highlight']) && $_GET['highlight'] == $row_data[$this->row_id_column]) {
                    $cur_row_attr->appendAttr('class', 'highlight', ' ');
                    PageHead::getInstance()
                        ->addJs('
                        $(function () {
                            // Scroll to highlighted element
                            $("body").delay(500).animate({scrollTop: ($(\'.highlight\').offset().top  - 300)}, 500);
                        });');
                }
            }
            if ($this->dragable) {
                $cur_row_attr->appendAttr('class', 'dnd_container', ' ');
            }

            if ($styles) $cur_row_attr->appendAttr('style', implode(';', $styles));

            if ($this->context_menu_items) {
                ?>
                <script>
                    cms_data.context_menu_items = function (el) {
                        var $el = $(el);
                        var items = $el.data('context-menu-items');

                        items = items.replace(/'/g, '"');
                        items = JSON.parse(items);

                        $.contextMenu({
                            selector: '#' + $el.attr('id'),
                            callback: function (key, options) {
                                var params = options.items[key];
                                if (typeof params.confirm != 'undefined' && params.confirm) {
                                    if (!confirm('<?= __('Are you sure?') ?>')) {
                                        return false;
                                    }
                                }

                                if (typeof params.href != 'undefined') {
                                    if (typeof params.popup != 'undefined' && params.popup) {
                                        window.open(params.href);
                                    } else {
                                        window.location = params.href;
                                    }
                                }
                            },
                            items: items
                        });
                    };
                </script>
                <?php
                $context_menu_js = [];

                $keys_for_replaces = ['href', 'title'];
                foreach ($this->context_menu_items as $item_key => $item) {
                    foreach ($keys_for_replaces as $key_for_replace) {

                        preg_match_all('/\{%([^\}]+)%\}/', $item[$key_for_replace], $matches);
                        if (!isset($matches[1]) || !$matches[1]) {
                            continue;
                        }
                        foreach ($matches[1] as $match) {
                            $item[$key_for_replace] = str_replace('{%' . $match . '%}', $row_data[$match], $item[$key_for_replace]);
                        }
                        $context_menu_js[] = '"' . $item_key . '": {"name": "' . $item['title'] . '", "href": "' . $item['href'] . '", "confirm": ' . $item['confirm'] . ', "popup": ' . $item['popup'] . '}';

                    }
                }

                // jQuery context menu
                $context_menu_js = '{' . implode(',', str_replace('"', "'", $context_menu_js)) . '}';
            }

            if ($context_menu_js) {
                $context_menu_js = 'oncontextmenu="cms_data.context_menu_items(this);" data-context-menu-items="' . $context_menu_js . '" ';
            } else {
                $context_menu_js = '';
            }

            echo '<tr ' . $context_menu_js . $cur_row_attr . '>' . $this->getRowView($i - 1, $row_data) . '</tr>';
            $i++;
        }

        if ($tbody) {
            ?></tbody><?php
        }

        return ob_get_clean();
    }

    /**
     * @param int $row
     * @param array $row_data
     * @return string
     */
    private function getRowView(int $row, array $row_data): string
    {
        ob_start();

        $this->linker->table_id = $this->id;
        foreach ($this->columns as $column) {
            /* @var $column Column */
            echo $column->getView($row, $row_data, $this->linker);
        }

        return ob_get_clean();
    }

    /**
     * @return array
     */
    private function getTreeColumnChildrenIds(): array
    {
        foreach ($this->columns as $column) {
            if ($column instanceof ColumnTree) {
                return $column->getChildIds();
            }
        }

        return [];
    }

    /**
     * @return string
     */
    private function getHeadersView(): string
    {
        ob_start();

        $sub_columns = [];
        echo '<thead><tr>';
        $out_put_groups = [];

        foreach ($this->columns as $column) {
            /* @var $column Column */

            if ($this->grouped_columns) {
                foreach ($this->grouped_columns as $group => $group_columns) {
                    foreach ($group_columns as $group_column) {
                        if ($group_column === $column->getKey()) {
                            $sub_columns[] = $column;
                            if (in_array($group, $out_put_groups)) {
                                continue 3;
                            }

                            echo '<th rowspan="1" align="center" colspan="' . \count($group_columns) . '" style="background: #eee; font-weight: 700;">' . $this->columns_groups[$group] . '</th>';
                            $out_put_groups[] = $group;
                            continue 3;
                        }
                    }
                }
                echo '<th rowspan="2"' . $column->getAttributesView() . '>' . $column->getTitleView($this->id) . '</th>';
            } else {
                echo '<th ' . $column->getAttributesView() . '>' . $column->getTitleView($this->id) . '</th>';
            }

        }
        echo '</tr>';
        if ($sub_columns) {
            echo '<tr>';
            foreach ($sub_columns as $column) {
                echo '<th ' . $column->getAttributesView() . '>' . $column->getTitleView($this->id) . '</th>';
            }
            echo '</tr>';
        }

        echo '</thead>';

        return ob_get_clean();
    }

    /**
     * @return string
     */
    private function getTotalView(): string
    {
        $count_of_columns = \count($this->columns);
        if (!$count_of_columns) {
            return '';
        }

        $totals = [
            'sum_filtered' => [
                'title' => 'Filtered SUM',
                'function' => 'getSumFiltered',
                'values' => [],
                'all_columns_with_total' => true,
                'title_pos' => -1,
                'title_colspan' => -1
            ],
            'avg_filtered' => [
                'title' => 'Filtered AVG',
                'function' => 'getAvgFiltered',
                'values' => [],
                'all_columns_with_total' => true,
                'title_pos' => -1,
                'title_colspan' => -1
            ],
            'sum_total' => [
                'title' => 'Total SUM',
                'function' => 'getSumTotal',
                'values' => [],
                'all_columns_with_total' => true,
                'title_pos' => -1,
                'title_colspan' => -1
            ],
            'avg_total' => [
                'title' => 'Total AVG',
                'function' => 'getAvgTotal',
                'values' => [],
                'all_columns_with_total' => true,
                'title_pos' => -1,
                'title_colspan' => -1
            ]
        ];

        foreach ($totals as &$total) {
            $l_p = -1;
            $r_p = -1;
            for ($i = 0; $i < $count_of_columns; $i++) {
                /* @var $column Column */
                $column = &$this->columns[$i];
                if ($column instanceof ColumnTree) {
                    /** @var ColumnTree $column */
                    $tree_level = $column->getMaxLevel();
                }
                $current_value = $column->{$total['function']}();

                if ($current_value === 0.0) { // Floats
                    if (!$total['values'] || $total['title_pos'] == -1) {
                        $total['title_pos'] = $i;
                    }
                    $total['all_columns_with_total'] = false;
                } else {
                    if ($l_p + 1 == $i) {
                        $l_p = $i;
                    } elseif ($r_p == -1) {
                        $r_p = $i;
                    }

                    $total['values'][$i] = $current_value;
                }

            }

            if ($total['values']) {
                if ($l_p == -1) {
                    $l_p = 0;
                }
                if ($r_p == -1) {
                    $r_p = $count_of_columns - 1;
                }
                $title_colspan = $r_p - $l_p;
                if (isset($tree_level)) {
                    $title_colspan -= $tree_level;
                }
                $total['title_colspan'] = $title_colspan;
            }
        }
        unset($total);

        $i = 0;
        foreach ($totals as $total) {
            $values[$i++] = array_keys($total['values']);
        }

        $values_count = \count($totals);
        $i = 0;

        foreach ($totals as $total) {
            if ($total['all_columns_with_total']) {
                dump('Error, all table columns contains ' . $total['title']);
            }
            $current_values_keys = $values[$i];
            for ($j = $i + 1; $j < $values_count; $j++) {
                if ($current_values_keys && $values[$j] && $current_values_keys !== $values[$j]) {
                    dump('Rows contains different totals columns - ' . $total['title']);
                }
            }
            $i++;
        }

        ob_start();
        $total_values_js = [];
        $total_titles_js = [];

        foreach ($totals as $type => $total) {
            if (!$total['values']) continue;
            $title_out_put = false;
            echo '<tr>';
            for ($i = 0; $i < $count_of_columns; $i++) {
                // Output value
                $column = $this->columns[$i];

                if (isset($total['values'][$i])) {
                    $align = $column->getAlign();
                    $column->enableRightAlign();
                    $id = 'td_' . $type . '_' . $i;
                    echo '<td id="' . $id . '" ' . $column->getAttributesView() . '>  ' . $total['values'][$i] . '</td>';
                    $column->setAlign($align);
                    $total_values_js[$type][] = $id;
                    // output title
                } elseif ($i === $total['title_pos']) {
                    $id = 'td_' . $type . '_' . $i;
                    $align = $column->getAlign();
                    $column->setAlign((isset($total['values'][$i - 1]) ? 'left' : 'right'));
                    echo '<td colspan="' . $total['title_colspan'] . '" ><span id="' . $id . '" style="color: #666" ' . $column->getAttributesView() . '>' . $total['title'] . '</span></td>';
                    $column->setAlign($align);
                    $total_titles_js[$type] = $id;
                    $title_out_put = true;
                } elseif ($title_out_put) {
                    echo '<td>&nbsp;</td>';
                }
            }

            echo '</tr>';
        }

        $total_tr = ob_get_clean();
        ob_start();

        ?>var Totals = {
        rows : <?= json_encode($total_values_js) ?>,
        titles: <?= json_encode($total_titles_js) ?>
        }<?php
        $this->js[] = ob_get_clean();

        return $total_tr;
    }

    /**
     * @return $this
     */
    public function enableEditable()
    {
        $this->editable_table = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function enableSaveIntoFile()
    {
        $this->enabled_save_into_file = true;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabledSaveIntoFile(): bool
    {
        return $this->enabled_save_into_file;
    }

    /**
     * @param array $actions
     *
     * @return $this
     */
    public function addMultiAction(array $actions)
    {
        foreach ($actions as $action) {
            $this->multiple_actions[] = $action;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getTableId(): string
    {
        return $this->id;
    }
}
