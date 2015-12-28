<?php

namespace neTpyceB\TMCms\Orm;

use neTpyceB\TMCms\DB\SQL;

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
        if (!$this->table_name) {
            trigger_error('Table name is not set');
        }

        if (SQL::tableExists($this->table_name)) {
            trigger_error('DB table "'. $this->table_name .'" already exists');
        }

        if (!$this->table_structure || !isset($this->table_structure['fields'])) {
            trigger_error('Table structure is not set');
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
                ]
            ];

            foreach ($tmp as $k => $v) {
                $this->table_structure['fields'][$k] = $v;
            }
        }

        // Start with creation
        $sql = 'CREATE TABLE IF NOT EXISTS `'. $this->getTableName() .'` ( ';

        // Add fields
        $fields = [];
        foreach ($this->table_structure['fields'] as $field_name => $field_data) {
            $field_data['name'] = $field_name;
            $fields[] = $this->getFieldCreate($field_data);
        }
        $sql  .= implode(', ', $fields);

        // Set pripary key
        $sql .= ', PRIMARY KEY (`id`) ';

        // Index keys
        $indexes = [];
        foreach ($this->table_structure['indexes'] as $index_name => $index_data) {
            $indexes[] = strtoupper($index_data['type']) . ' `'. $index_name .'` (`'. $index_name .'`)';
        }
        if ($indexes) {
            $sql  .= ', ' . implode(', ', $indexes);
        }

        // Set engine and encoding
        $sql .= ' ) ENGINE=InnoDB ';
        $sql .= ' AUTO_INCREMENT=1 ';
        $sql .= ' DEFAULT CHARSET=utf8 ';

        q($sql);
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
                $res = '`'. $field['name'] .'` varchar('. $field['length'] .') NOT NULL';
                break;

            case 'char':
                // Codes
                if (!isset($field['length'])) {
                    trigger_error('Length for "'. $field['name'] .'" required');
                }
                $res = '`'. $field['name'] .'` char('. $field['length'] .') NOT NULL';
                break;

            case 'text':
                // Large textares
                $res = '`'. $field['name'] .'` text NOT NULL';
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
                $res = '`'. $field['name'] .'` int('. $field['length'] .') '. ($unsigned ? ' unsigned ' : '') .' NOT NULL ' . (isset($field['auto_increment']) && $field['auto_increment'] ? ' AUTO_INCREMENT ' : '');
                break;

            case 'float':
                // Decimal
                $unsigned = isset($field['unsigned']) && $field['unsigned'];
                if (!isset($field['length'])) {
                    $field['length'] = 8.2;
                }
                // Convert to db format
                $field['length'] = str_replace('.', ',', $field['length']);

                $res = '`'. $field['name'] .'` decimal('. $field['length'] .') '. ($unsigned ? ' unsigned ' : '') .' NOT NULL ' . (isset($field['auto_increment']) && $field['auto_increment'] ? ' AUTO_INCREMENT ' : '');
                break;

            default:
                trigger_error('Type "'. $field['type'] .'" not found in TableStructure');
        }

        return $res;
    }
}