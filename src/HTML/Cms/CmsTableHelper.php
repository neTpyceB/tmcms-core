<?php

namespace TMCms\HTML\Cms;

use TMCms\HTML\Cms\Column\ColumnActive;
use TMCms\HTML\Cms\Column\ColumnData;
use TMCms\HTML\Cms\Column\ColumnDelete;
use TMCms\HTML\Cms\Column\ColumnEdit;
use TMCms\HTML\Cms\Filter\Select;
use TMCms\HTML\Cms\Filter\Text;

class CmsTableHelper {
    public static function outputTable(array $params) {
        // Check columns
        if (!isset($params['columns'])) {
            $params['columns'] = [];
        }

        // Check edit column
        if (isset($params['edit']) && !isset($params['columns']['edit'])) {
            $params['columns']['edit'] = [
                'type' => 'edit',
            ];
        }
        // Check active column
        if (isset($params['active']) && !isset($params['columns']['active'])) {
            $params['columns']['active'] = [
                'type' => 'active',
            ];
        }
        // Check delete column
        if (isset($params['delete']) && !isset($params['columns']['delete'])) {
            $params['columns']['delete'] = [
                'type' => 'delete',
            ];
        }

        // Check params are supplied
        if (!isset($params['data'])) {
            $params['data'] = [];
        }

        $table = new CmsTable();
        $table->addData($params['data']);

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
                case 'edit':
                    $column = new ColumnEdit($column_key);
                    break;
                case 'active':
                    $column = new ColumnActive($column_key);
                    break;
                case 'delete':
                    $column = new ColumnDelete($column_key);
                    break;
            }

            // Title
            if (isset($column_param['title'])) {
                $column->setTitle($column_param['title']);
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

            // Paired array
            if (isset($column_param['pairs'])) {
                $column->setPairedDataOptionsForKeys($column_param['pairs']);
            }

            if ($column) {
                $table->addColumn($column);
            }
        }

        // Apply filter
        if (isset($params['filters']) || $params['caption']) {
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
                        $filter_data['title'] = $filter_key;
                    }

                    //Filter types
                    $filter = NULL;
                    switch ($filter_data['type']) {
                        case 'text':
                            $filter = Text::getInstance($filter_key);
                            break;
                        case 'select':
                            $filter = Select::getInstance($filter_key);
                            break;
                    }

                    // Options for selects
                    if (isset($filter_data['options'])) {
                        $filter->setOptions($filter_data['options']);
                    }

                    // Ignore values in selects
                    if (isset($filter_data['ignore'])) {
                        $filter->ignoreValue($filter_data['ignore']);
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
}