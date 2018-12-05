<?php
declare(strict_types=1);

namespace TMCms\DB\Adapter;

use TMCms\DB\Entity\MySqlTableColumnEntity;
use TMCms\DB\SQL;
use TMCms\Orm\TableStructure;

\defined('INC') or exit;

/**
 * Class MySQL
 */
class MySQL extends SQL
{
    /**
     * @param string $table
     * @param string $name
     * @param string $type
     * @param array $options
     * @return string generated SQL to be run
     * @throws \InvalidArgumentException
     */
    public static function generateCreateColumnSQL(string $table, string $name, string $type, array $options = []): string
    {
        $column = new MySqlTableColumnEntity();
        $column->setDbTableName($table);
        $column->setName($name);

        if ($type === TableStructure::FIELD_TYPE_BOOL) {
            $type = $column::COLUMN_TYPE_INT;
            if (!isset($options['length'])) {
                $options['length'] = 1;
            }
            if (!isset($options['default_value'])) {
                $options['default_value'] = 0;
            }
            if (!isset($options['unsigned'])) {
                $options['unsigned'] = true;
            }
        }

        $column->setType($type);

        if (isset($options['unsigned'])) {
            $column->setUnsigned($options['unsigned']);
        }

        if (isset($options['length'])) {
            $column->setLength($options['length']);
        }

        if (isset($options['default_value'])) {
            $column->setDefaultValue($options['default_value']);
        }

        return $column->getCreateStatement();
    }
}
