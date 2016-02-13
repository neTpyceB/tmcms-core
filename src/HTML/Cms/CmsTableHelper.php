<?php

namespace TMCms\HTML\Cms;

use TMCms\HTML\Cms\Column\ColumnActive;
use TMCms\HTML\Cms\Column\ColumnData;
use TMCms\HTML\Cms\Column\ColumnDelete;
use TMCms\HTML\Cms\Column\ColumnEdit;

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

            if ($column) {
                $table->addColumn($column);
            }
        }

        return $table;
    }
}