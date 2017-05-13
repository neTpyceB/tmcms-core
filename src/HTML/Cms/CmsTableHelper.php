<?php

namespace TMCms\HTML\Cms;

use TMCms\DB\SQL;
use TMCms\HTML\Cms\Column\ColumnAccept;
use TMCms\HTML\Cms\Column\ColumnActive;
use TMCms\HTML\Cms\Column\ColumnCheckbox;
use TMCms\HTML\Cms\Column\ColumnData;
use TMCms\HTML\Cms\Column\ColumnDelete;
use TMCms\HTML\Cms\Column\ColumnDone;
use TMCms\HTML\Cms\Column\ColumnEdit;
use TMCms\HTML\Cms\Column\ColumnGallery;
use TMCms\HTML\Cms\Column\ColumnImg;
use TMCms\HTML\Cms\Column\ColumnInput;
use TMCms\HTML\Cms\Column\ColumnOrder;
use TMCms\HTML\Cms\Column\ColumnTree;
use TMCms\HTML\Cms\Column\ColumnView;
use TMCms\HTML\Cms\Filter\Select;
use TMCms\HTML\Cms\Filter\Text;
use TMCms\Orm\EntityRepository;
use TMCms\Strings\Converter;

class CmsTableHelper {
    public static function outputTable(array $params) {
        // Check data is supplied
        if (!isset($params['data'])) {
            $params['data'] = [];
        }

        // Check columns
        if (!isset($params['columns'])) {
            $params['columns'] = [];
        }

        // Generate fields from DB and combine with provided params
        if (isset($params['combine']) && $params['combine']) {
            $params['columns'] = array_merge(self::combineParamsFromDB($params), $params['columns']);
        }

        // Check view column
        if (isset($params['view']) && !isset($params['columns']['view'])) {
            $params['columns']['view'] = [
                'type' => 'view',
            ];
        }
        // Check order column
        if (isset($params['order']) && !isset($params['columns']['order'])) {
            $params['columns']['order'] = [
                'type' => 'order',
            ];
        }
        // Check active column
        if (isset($params['active']) && !isset($params['columns']['active'])) {
            $params['columns']['active'] = [
                'type' => 'active',
            ];
        }
        // Check edit column
        if (isset($params['edit']) && !isset($params['columns']['edit'])) {
            $params['columns']['edit'] = [
                'type' => 'edit',
            ];
        }
        // Check delete column
        if (isset($params['delete']) && !isset($params['columns']['delete'])) {
            $params['columns']['delete'] = [
                'type' => 'delete',
            ];
        }

        $table = new CmsTable();
        $table->addData($params['data']);

        // Table title
        if (isset($params['title'])) {
            $table->setHeadingTitle($params['title']);
        }

        // Turn off pagination
        if (isset($params['pager']) && !$params['pager']) {
            $table->disablePager();
        }

        // Set callback function
        if (isset($params['callback_function'])) {
            $table->setCallbackFunction($params['callback_function']);
        }

        // Set callback function
        if (isset($params['context_menu'])) {
            $table->addContextMenu($params['context_menu']);
        }

        foreach ($params['columns'] as $column_key => $column_param) {
            if (!is_array($column_param)) {
                $column_key = $column_param;
                $column_param = [
                    'type' => 'data',
                ];
            }

            if (!isset($column_param['type'])) {
                $column_param['type'] = 'data';
            }

            $column = NULL;

            switch ($column_param['type']) {
                case 'data':

                    $column = new ColumnData($column_key);

                    break;

                case 'date':

                    $column = new ColumnData($column_key);
                    $column->setDataTypeAsTsToDate();

                    break;


                case 'datetime':

                    $column = new ColumnData($column_key);
                    $column->setDataTypeAsTsToDatetime();

                    break;

                case 'email':

                    $column = new ColumnData($column_key);
                    $column->setDataTypeAsEmail();

                    break;

                case 'input':

                    $column = new ColumnInput($column_key);
                    // Type os input
                    if (isset($column_param['input'])) {
                        switch ($column_param['input']) {
                            default:
                            case 'text':
                                $column->setTypeText();
                                break;
                            case 'textarea':
                                $column->setTypeTextarea();
                                break;
                            case 'select':
                                if (!isset($column_param['options'])) {
                                    dump('Options must be provided for column input with select type');
                                }

                                $column->setTypeSelect();
                                $column->setOptions($column_param['options']);
                                break;
                        }
                    }

                    break;

                case 'tree':

                    $table->disablePager();

                    $tree_show_key = $column_key;
                    if (isset($column_param['show_key'])) {
                        $tree_show_key = $column_param['show_key'];
                    }

                    $tree_main_key = 'id';
                    if (isset($column_param['key'])) {
                        $tree_main_key = $column_param['key'];
                    }

                    $column = new ColumnTree($tree_main_key);
                    $column
                        ->setShowKey($tree_show_key)
                        ->allowHtml()
                        ->enableAjax()
                        ->setWidth('1%');

                    break;

                case 'accept':
                    $column = new ColumnAccept($column_key);
                    break;
                case 'order':
                    $column = new ColumnOrder($column_key);
                    break;
                case 'edit':
                    $column = ColumnEdit::getInstance($column_key);
                    break;
                case 'view':
                    $column = ColumnView::getInstance($column_key);
                    break;
                case 'checkbox':
                    $column = ColumnCheckbox::getInstance($column_key);
                    break;
                case 'active':
                    $column = ColumnActive::getInstance($column_key);
                    break;
                case 'delete':
                    $column = ColumnDelete::getInstance($column_key);
                    break;
                case 'gallery':
                    $column = new ColumnGallery($column_key);
                    break;
                case 'image':
                    $column = new ColumnImg($column_key);
                    break;
                case 'bool':
                case 'done':
                    $column = new ColumnDone($column_key);
                    break;

                default:

                    dump('Unknown column type "'. $column_param['type'] .'"');

                    break;
            }

            // Disable cutting long texts by column
            if (isset($column_param['cut_long_strings'])) {
                if (!$column_param['cut_long_strings']) {
                    $column->disableCutLongStrings();
                }
            }

            // Disable cutting long texts by entire table
            if (isset($params['cut_long_strings'])) {
                if (!$params['cut_long_strings']) {
                    $column->disableCutLongStrings();
                }
            }

            // Is multi-translatable data in column
            if (isset($column_param['translation']) && $column_param['translation']) {
                $column->enableTranslationColumn();
            }

            // HTML in column value
            if (isset($column_param['html']) && $column_param['html']) {
                $column->allowHtml();
            }

            // Is orderable
            if (isset($column_param['order']) && $column_param['order']) {
                $column->enableOrderableColumn();
            }

            // Is dragable
            if (isset($column_param['order_drag']) && $column_param['order_drag']) {
                $column->enableDraggable();
            }

            // Title
            if (isset($column_param['title'])) {
                $column->setTitle($column_param['title']);
            }

            // Images for gallery
            if (isset($column_param['images'])) {
                $column->setImages($column_param['images']);
            }

            // Width of column
            if (isset($column_param['narrow'])) {
                $column->enableNarrowWidth();
            }
            if (isset($column_param['width'])) {
                $column->setWidth($column_param['width']);
            }

            // Link
            if (isset($column_param['href'])) {
                $column->setHref($column_param['href']);
            }
            if (isset($column_param['href_new_window'])) {
                $column->href_new_window($column_param['href_new_window']);
            }

            // Paired array
            if (isset($column_param['pairs'])) {
                $column->setPairedDataOptionsForKeys($column_param['pairs']);
            }

            // Value for column
            if (isset($column_param['value'])) {
                $column->setValue($column_param['value']);
            }

            // nl2br
            if (isset($column_param['nl2br'])) {
                $column->enbleNl2Br();
            }

            // Add to filters
            if (isset($column_param['filter']) && !isset($params['filters'][$column_key])) {
                $params['filters'][$column_key] = [];
            }

            // Show auto-sum after column
            if (isset($column_param['auto_sum'])) {
                $column->enableAutoTotalInColumn();
            }

            // Align for text in column
            if (isset($column_param['align']) && $column_param['align'] == 'right') {
                $column->enableRightAlign();
            }

            // No wrap of content
            if (isset($column_param['nowrap']) && $column_param['nowrap']) {
                $column->disableNewlines();
            }

            // Javascript onclick
            if (isset($column_param['onclick'])) {
                $column->onclick($column_param['onclick']);
            }

            // Javascript onchange
            if (isset($column_param['onchange'])) {
                $column->onchange($column_param['onchange']);
            }

            // Data attributes
            if (isset($column_param['data_attributes'])) {
                if (!is_array($column_param['data_attributes'])) {
                    dump('Parameter "data_attribute" must be array');
                }
                foreach ($column_param['data_attributes'] as $data_attribute_key => $data_attribute_value) {
                    $column->addDataAttribute($data_attribute_key, $data_attribute_value);
                }
            }

            if ($column) {
                $table->addColumn($column);
            }
        }

        // Apply filter
        if (isset($params['filters']) || isset($params['caption'])) {
            $filter_form = new FilterForm;

            // Top caption above table
            if (isset($params['caption'])) {
                $filter_form->setCaption($params['caption']);
            }

            // Render filters
            if (isset($params['filters'])) {
                foreach ($params['filters'] as $filter_key => $filter_data) {
                    // Title is obligate
                    if (!isset($filter_data['title'])) {
                        $name = $filter_key;
                        if (substr($name, -3) == '_id') {
                            $name = substr($name, 0, -3);
                        }
                        $filter_data['title'] = Converter::symb2Ttl($name);
                    }

                    // If have options that probably this is select
                    if (!isset($filter_data['type']) && isset($filter_data['options'])) {
                        $filter_data['type'] = 'select';
                    }

                    // Default type
                    if (!isset($filter_data['type'])) {
                        $filter_data['type'] = 'text';
                        $filter_data['like'] = true;
                    }

                    //Filter types
                    $filter = NULL;
                    switch ($filter_data['type']) {
                        case 'text':
                            $filter = Text::getInstance($filter_key);
                            break;
                        case 'select':
                            $filter = Select::getInstance($filter_key);
                            $filter->ignoreValue(-1); // For "empty" value
                            break;
                        default:
                            dump('Unknown filter type "'. $filter_data['type'] .'"');
                            break;
                    }

                    // Autosubmit
                    if (isset($filter_data['auto_submit']) && $filter_data['auto_submit']) {
                        $filter->enableAutoSubmit();
                    }

                    // Options for selects
                    if (isset($filter_data['options'])) {
                        $filter->setOptions($filter_data['options']);
                    }

                    // Ignore values in selects
                    if (isset($filter_data['ignore'])) {
                        $filter->ignoreValue($filter_data['ignore']);
                    }

                    // Like search
                    if (isset($filter_data['like'])) {
                        $filter->enableActAsLike();
                    }

                    if ($filter) {
                        $filter_form->addFilter($filter_data['title'], $filter);
                    }
                }
            }

            $table->attachFilterForm($filter_form);
        }

        return $table;
    }

    private static function combineParamsFromDB($params)
    {
        $table = '';
        if ($params['data'] && is_object($params['data'])) {
            /** @var EntityRepository $entity_repository */
            $entity_repository = $params['data'];
            $table = $entity_repository->getDbTableName();
        }

        // No table to fetch columns from
        if (!$table) {
            return [];
        }

        // Fields from DB
        $columns = [];
        foreach (SQL::getTableColumns($table) as $v) {
            $column = [];

            // Skip auto_increment field
            if ($v['Field'] == 'id') {
                continue;
            }

            // Email conversion to link
            if ($v['Field'] == 'email') {
                $column['type'] = 'email';
            }

            // Remove active column, it will be auto-rendered
            if ($v['Field'] == 'active') {
                continue;
            }

            // Checkboxes
            if ($v['Type'] == 'tinyint(1) unsigned') {
                $column['type'] = 'active';
            }

            $columns[$v['Field']] = $column;
        }

        return $columns;
    }
}