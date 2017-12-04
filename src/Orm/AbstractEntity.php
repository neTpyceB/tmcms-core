<?php
declare(strict_types=1);

namespace TMCms\Orm;
use TMCms\DB\SQL;
use TMCms\DB\SqlDao;
use TMCms\Strings\Converter;

/**
 * Class AbstractEntity
 * @package TMCms\Orm
 */
abstract class AbstractEntity
{
    const CLASS_RELATION_NAME_ENTITY = 'Entity'; // Represents one object with possible state saved in DB
    const CLASS_RELATION_NAME_REPOSITORY = 'Repository'; // Represents a collection of objects and DB table with their states

    protected $db_table = '';
    protected $debug = false;
    protected $translation_fields = [];

    /**
     * @var SqlDao
     */
    protected $dao;

    /**
     * Return name in class or try to get from class name
     *
     * @return string
     */
    public function getDbTableName(): string
    {
        // Name set in class
        if ($this->db_table) {
            return $this->db_table;
        }

        $db_table_from_class = mb_strtolower(Converter::fromCamelCase(str_replace([self::CLASS_RELATION_NAME_ENTITY, self::CLASS_RELATION_NAME_REPOSITORY], '', $this->getUnqualifiedShortClassName()))) . 's';

        // Check DB in system tables
        $this->db_table = 'cms_' . $db_table_from_class;
        if (!SQL::getInstance()->tableExists($this->db_table)) {
            // Or in module tables
            $this->db_table = 'm_' . $db_table_from_class;
        }

        return $this->db_table;
    }

    /**
     * @return string
     */
    public function getUnqualifiedShortClassName(): string
    {
        return Converter::classWithNamespaceToUnqualifiedShort($this);
    }

    /**
     * @return $this
     */
    public function enableDebug()
    {
        $this->debug = true;

        return $this;
    }

    /**
     * @param mixed $data
     */
    protected function debug($data)
    {
        if (!$this->debug) {
            return;
        }

        dump($data);
    }

    /**
     * @param SqlDao $dao
     * @return $this
     */
    public function setSqlDaoObject($dao) {
        $this->dao = $dao;

        return $this;
    }
}
