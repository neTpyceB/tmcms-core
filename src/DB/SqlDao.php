<?php
declare(strict_types=1);

namespace TMCms\DB;

use PDO;

\defined('INC') or exit;

/**
 * Class SqLDao
 * @package TMCms\DB
 */
abstract class SqlDao extends Dao
{
    /** @var PDO */
    protected $pdo_db;

    protected static $_table_list = []; // All tables in database
    protected static $cached_create_table_statements = [];  // All tables CREATE statements
    protected static $_cached_tbl_fields = []; // All table fields
    protected static $_cached_tbl_columns = [];

    /**
     * @param string $name
     *
     * @return bool
     */
    public function functionExist($name): bool
    {
        $function = $this->getFunctions();

        return \in_array($name, $function, true);
    }

    /**
     * @param null $db
     *
     * @return array
     */
    abstract public function getFunctions($db = NULL): array;
}
