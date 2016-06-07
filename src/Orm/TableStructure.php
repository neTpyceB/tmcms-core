<?php

namespace TMCms\Orm;

use TMCms\DB\SQL;
use TMCms\DB\Sync;

class TableStructure {

    private $table_name = '';
    private $table_structure = [];

    public function setTableName($name)
    {
        $this->table_name = $name;

        return $this;
    }

    public function getTableName()
    {
        return $this->table_name;
    }

    public function setTableStructure(array $structure)
    {
        $this->table_structure = $structure;

        return $this;
    }

    public function createTableIfNotExists()
    {
        if (SQL::tableExists($this->table_name)) {
            dump('DB table "'. $this->table_name .'" already exists');
        }

        $sql = $this->getCreateTableStatement();
        q($sql);

        return true;
    }

    public function getCreateTableStatement()
    {
        if (!$this->table_name) {
            dump('Table name is not set');
        }

        if (!$this->table_structure || !isset($this->table_structure['fields'])) {
            dump('Table "'. $this->table_name .'" does not exist and structure is not set');
        }

        if (!isset($this->table_structure['indexes'])) {
            $this->table_structure['indexes'] = [];
        }

        if (!isset($this->table_structure['fields']['id'])) {
            $tmp = $this->table_structure['fields'];

            $this->table_structure['fields'] = [
                'id' => [
                    'type' => 'int',
                    'unsigned' => true,
                    'length' => 10,
                    'auto_increment' => true,
                    'null' => false,
                ]
            ];

            foreach ($tmp as $k => $v) {
                $this->table_structure['fields'][$k] = $v;
            }
        }

        // Start with creation
        $sql = 'CREATE TABLE `'. $this->getTableName() .'` ( ';

        // Add fields
        $fields = [];
        foreach ($this->table_structure['fields'] as $field_name => $field_data) {
            $field_data['name'] = $field_name;
            $fields[] = $this->getFieldCreate($field_data);
        }
        $sql  .= implode(', ', $fields);

        // Set pripary key
        $sql .= ', PRIMARY KEY (`id`)';

        // Index keys
        $indexes = [];
        foreach ($this->table_structure['indexes'] as $index_name => $index_data) {
            $indexes[] = strtoupper($index_data['type']) . ' `'. $index_name .'` (`'. $index_name .'`)';
        }
        if ($indexes) {
            $sql  .= ', ' . implode(', ', $indexes);
        }

        // Set engine and encoding
        $sql .= ') ENGINE=MyISAM ';
        $sql .= ' AUTO_INCREMENT=1 ';
        $sql .= ' DEFAULT CHARSET=utf8 ';

        return $sql;
    }

    private function getFieldCreate($field)
    {
        $res = '';

        switch ($field['type']) {
            case 'varchar':
                // Simple text input
                if (!isset($field['length'])) {
                    $field['length'] = '255';
                }
                $res = '`'. $field['name'] .'` varchar('. $field['length'] .') NULL DEFAULT ""';
                break;

            case 'char':
                // Codes
                if (!isset($field['length'])) {
                    dump('Length for "'. $field['name'] .'" required');
                }
                $res = '`'. $field['name'] .'` char('. $field['length'] .') DEFAULT NULL';
                break;

            case 'text':
                // Large textares
                $res = '`'. $field['name'] .'` text NULL DEFAULT ""';
                break;

            case 'mediumtext':
                // Large textares
                $res = '`'. $field['name'] .'` mediumtext NULL DEFAULT ""';
                break;

            case 'int':
                // Digit
                $unsigned = isset($field['unsigned']) && $field['unsigned'];
                if (!isset($field['length'])) {
                    $field['length'] = 11;
                    if ($unsigned) {
                        $field['length'] = 10;
                    }
                }
                $res = '`'. $field['name'] .'` int('. $field['length'] .') '. ($unsigned ? ' unsigned ' : '') . (isset($field['null']) && !$field['null'] ? ' NOT NULL' : ((isset($field['auto_increment']) ? '' : ' DEFAULT') . ' NULL'));
                break;

            case 'ts':
                // Digit
                $res = '`'. $field['name'] .'` int(10) unsigned NULL DEFAULT "0"';
                break;

            case 'index':
                $res = '`'. $field['name'] .'` int(10) unsigned DEFAULT "0"';

                // Add index if not exists
                if (!isset($this->table_structure['indexes'][$field['name']])) {
                    $this->table_structure['indexes'][$field['name']] = [
                        'type' => 'key',
                    ];
                }
                break;

            case 'translation':
                // Int index with comment
                $res = '`'. $field['name'] .'` int(10) unsigned NULL DEFAULT "0"';
                $field['comment'] = 'translation';
                break;

            case 'bool':
                // True or false, 0 | 1
                $res = '`'. $field['name'] .'` tinyint(1) unsigned NULL DEFAULT "0"';
                break;

            case 'float':
                // Decimal
                $unsigned = isset($field['unsigned']) && $field['unsigned'];
                if (!isset($field['length'])) {
                    $field['length'] = 8.2;
                }
                // Convert to db format
                $field['length'] = str_replace('.', ',', $field['length']);

                $res = '`'. $field['name'] .'` decimal('. $field['length'] .') '. ($unsigned ? ' unsigned ' : '') .' NULL DEFAULT "0"';
                break;

            case 'enum':
                if (!isset($field['options'])) {
                    dump('Param "options" must be set for field "enum"');
                }

                $res = '`'. $field['name'] .'` enum("'. implode('","', $field['options']) .'") NULL';
                break;

            default:
                dump('Type "'. $field['type'] .'" not found in TableStructure');
        }

        if (isset($field['auto_increment'])) {
            $res .= ' AUTO_INCREMENT';
        }

        if (isset($field['comment'])) {
            $res .= ' COMMENT "'. $field['comment'] .'"';
        }

        return $res;
    }

    public function resetAutoIncrement()
    {
        if (!$this->table_name) {
            dump('Table name is not set');
        }

        SQL::getInstance()->sql_query('ALTER TABLE `'. $this->table_name .'` AUTO_INCREMENT = 1');

        return $this;
    }

    public function ensureDbTableStructureIsFresh()
    {
        $source = SQL::getCreateTable($this->getTableName());
        $destination = $this->getCreateTableStatement();

        $sync = new Sync();
        $sync->makeItWork();
        $sql = $sync->getUpdates($source, $destination);

        // Commented - do not auto run migrations, but only show desired changes
//        if ($sql) {
//            foreach ($sql as $item) {
//                q($item);
//            }
//        }

        return $sql;
    }
}