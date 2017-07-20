<?php
declare(strict_types=1);

namespace TMCms\Orm;

use ArrayIterator;
use IteratorAggregate;
use TMCms\Cache\Cacher;
use TMCms\Config\Settings;
use TMCms\DB\SQL;
use TMCms\Files\FileSystem;
use TMCms\Strings\Converter;
use Traversable;

class EntityRepository implements IteratorAggregate
{
    private static $_cache_key_prefix = 'orm_entity_repository_'; // Should be overwritten in extended class
    protected $db_table = ''; // Should be overwritten in extended class
    protected $translation_fields = []; // Should be overwritten in extended class
    protected $table_structure = [];
    protected $debug = false;
    private $sql_where_fields = [];
    private $sql_select_fields = [];
    private $sql_offset = 0;
    private $sql_limit = 0;
    private $order_fields = [];
    private $order_random = false;
    private $group_by_fields = [];
    private $having_fields = [];
    private $translation_join_count = 0;
    private $translation_join_alias = '';
    private $use_iterator = true;
    private $collected_objects = [];
    /** @var iterable */
    private $collected_objects_data = [];
    private $total_count_rows;
    private $require_to_count_total_rows = false;
    private $use_cache = false;
    private $cache_ttl = 60;
    private $lng;

    private $join_tables = [];
    private $last_used_sql;

    public function __construct(array $ids = [])
    {
        if (!Settings::isProductionState()) {
            // Create or update table
            $this->ensureDbTableExists();
        }

        if ($ids) {
            $this->setIds($ids);
        }
    }

    /**
     * @return bool table exists
     */
    public function ensureDbTableExists(): bool
    {
        $table = $this->getDbTableName();
        // May be empty
        if ($table === 'm_s') {
            return true;
        }

        $schema = new TableStructure();
        $schema->setTableName($this->getDbTableName());
        $schema->setTableStructure($this->getTableStructure());

        if (!SQL::tableExists($table)) {
            // Create table;
            $schema->createTableIfNotExists();
        }

        // Update structure using auto-created migrations
//        $schema->ensureDbTableStructureIsFresh(); // This changes a lot of required items, do not use in future

        return true;
    }

    /**
     * Return name in class or try to get from class name
     * @return string
     */
    public function getDbTableName(): string
    {
        // Name set in class
        if ($this->db_table) {
            return $this->db_table;
        }

        $db_table_from_class = mb_strtolower(Converter::from_camel_case(str_replace(['Entity', 'Repository'], '', Converter::classWithNamespaceToUnqualifiedShort($this)))) . 's';

        // Check DB in system tables
        $this->db_table = 'cms_' . $db_table_from_class;
        if (!SQL::tableExists($this->db_table)) {
            // Or in module tables
            $this->db_table = 'm_' . $db_table_from_class;
        }

        return $this->db_table;
    }

    protected function getTableStructure(): array
    {
        return $this->table_structure;
    }

    /**
     * @param array $ids
     * @return $this
     */
    public function setIds(array $ids)
    {
        $this->addWhereFieldIn('id', $ids);

        return $this;
    }

    /**
     * Filter collection by value inclusive
     * @param $field
     * @param array $values
     * @param string $table
     * @return $this
     */
    public function addWhereFieldIn($field, array $values, $table = '')
    {
        if (!$table) {
            $table = $this->getDbTableName();
        }

        if (!$values) {
            $values = [NULL];
        }
        foreach ($values as $k => & $v) {
            $v = sql_prepare($v);
        }
        unset($v);

        $this->addWhereFieldAsString('`' . $table . '`.`' . $field . '` IN ("' . implode('", "', $values) . '")');

        return $this;
    }

    public function addWhereFieldAsString($sql)
    {
        $this->sql_where_fields[] = [
            'table' => false,
            'field' => false,
            'value' => $sql,
            'type'  => 'string'
        ];

        return $this;
    }

    public static function getInstance(array $ids = [])
    {
        return new static($ids);
    }

    /**
     * Return array of Entity by array of criteria
     *
     * @param array $criteria select AND
     * @param array $exclude  select NOT
     *
     * @return array
     */
    public static function findAllEntitiesByCriteria(array $criteria = [], array $exclude = []): array
    {
        $class = static::class;

        /** @var EntityRepository $obj_collection */
        $obj_collection = new $class();

        foreach ($criteria as $k => $v) {
            $obj_collection->addSimpleWhereField($k, $v);
        }

        foreach ($exclude as $k => $v) {
            $obj_collection->addWhereFieldIsNot($k, $v);
        }

        return $obj_collection->getAsArrayOfObjects();
    }

    /**
     * @param string $field
     * @param string $value
     * @param string $table
     * @return $this
     */
    protected function addSimpleWhereField($field, $value = '', $table = '')
    {// No table provided
        if (!$table) {
            $table = $this->getDbTableName();
        }

        // Translation field
        if (in_array($field, $this->getTranslationFields(), true)) {
            ++$this->translation_join_count;
            $this->addJoinTable(['cms_translations', $this->getTranslationTableJoinAlias() . $this->translation_join_count], 'id', $field, 'LEFT', $table);

            $this->sql_where_fields[] = [
                'table' => $this->getTranslationTableJoinAlias() . $this->translation_join_count . '',
                'field' => $this->getLanguage(),
                'value' => $value,
                'type'  => 'simple'
            ];

            return $this;
        }

        // Simple field
        $this->sql_where_fields[] = [
            'table' => $table,
            'field' => $field,
            'value' => $value,
            'type'  => 'simple'
        ];

        return $this;
    }

    public function getTranslationFields(): array
    {
        return $this->translation_fields;
    }

    public function addJoinTable($table, $on_left, $on_right, $type = '', $right_table = null)
    {
        $alias = $table;
        if (is_array($table)) {
            list($table, $alias) = $table;
        }

        $this->join_tables[] = [
            'table'       => $table,
            'alias'       => $alias,
            'left'        => $on_left,
            'right'       => $on_right,
            'right_table' => $right_table ?: $this->getDbTableName(),
            'type'        => $type
        ];

        return $this;
    }

    private function getTranslationTableJoinAlias(): string
    {
        if (!$this->translation_join_alias) {
            $this->translation_join_alias = $this->getDbTableName() . '_translations';
        }

        return $this->translation_join_alias;
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->lng ?: LNG;
    }

    /**
     * Filter collection by skipping value
     * @param $field
     * @param string $value
     * @param string $table
     * @return $this
     */
    public function addWhereFieldIsNot($field, $value, $table = '')
    {
        if (!$table) {
            $table = $this->getDbTableName();
        }

        $this->addWhereFieldAsString('`' . $table . '`.`' . $field . '` != "' . sql_prepare($value) . '"');

        return $this;
    }

    /**
     * @return array
     */
    public function getAsArrayOfObjects(): array
    {
        $this->collectObjects();

        return $this->getCollectedObjects();
    }

    /**
     * @param bool $skip_objects_creation - true if no need to create objects
     * @param bool $skip_changed_fields - skip update of changed fields
     * @return $this
     */
    protected function collectObjects($skip_objects_creation = false, $skip_changed_fields = false)
    {
        $sql = $this->getSelectSql();
        if ($this->last_used_sql && $sql === $this->last_used_sql) {
            // Skip queries - nothing changed
            return $this;
        }
        $this->last_used_sql = $sql;

        // Check cache for this exact collection
        if ($this->use_cache) {
            //Check cached values, set local properties
            $data = Cacher::getInstance()->getDefaultCacher()->get($this->getCacheKey($sql));
            if ($data && is_array($data) && isset($data['collected_objects_data'], $data['collected_objects'])) {
                // Set local data
                $this->collected_objects_data = $data['collected_objects_data'];
                $this->collected_objects = $data['collected_objects'];

                // No further actions
                return $this;
            }
        }

        // Use Iterator in DB query
        if ($this->use_iterator) {
            $this->collected_objects_data = SQL::q_assoc_iterator($sql, false);
        } else {
            $this->collected_objects_data = SQL::q_assoc($sql, false);
        }

        if ($this->require_to_count_total_rows) {
            $this->total_count_rows = q_value('SELECT FOUND_ROWS();');
        }

        $this->collected_objects = []; // Reset objects

        if (!$skip_objects_creation) {
            // Need to create objects from array data
            foreach ($this->collected_objects_data as $v) {
                $class = $this->getObjectClass();
                /** @var Entity $obj */
                $obj = new $class();

                // Prevent auto-query db, skip tables with no id field
                if (!isset($v['id'])) {
                    dump('No `id` field present saving  "' . get_class($this) . '"');
                }
                $id = $v['id'];
                unset($v['id']);

                // Set object data
                $obj->loadDataFromArray($v, $skip_changed_fields);

                // Set current ID
                $obj->setId($id, false);

                // Save in returning array ob objects
                $this->collected_objects[] = $obj;
            }
        }

        if ($this->use_cache) {
            // Save all collected data to Cache
            $data = [
                'collected_objects_data' => $this->collected_objects_data,
                'collected_objects'      => $this->collected_objects
            ];
            Cacher::getInstance()->getDefaultCacher()->set($this->getCacheKey($sql), $data, $this->cache_ttl);
        }

        return $this;
    }

    public function getSelectSql($for_max_object_count = false): string
    {
        // Select
        if ($this->getSelectFields()) {
            $select_sql = [];
            foreach ($this->getSelectFields() as $field_data) {
                // Simple select
                if ($field_data['type'] === 'simple') {
                    $select_sql[] = '`' . $field_data['table'] . '`.`' . $field_data['field'] . '`' . ($field_data['as'] ? ' AS `' . $field_data['as'] . '`' : '');
                } elseif ($field_data['type'] === 'string') {
                    $select_sql[] = $field_data['field'];
                } elseif ($field_data['type'] === 'translation') {
                    $select_sql[] = '`' . $field_data['table'] . '`.`' . $field_data['field'] . '` AS `' . $field_data['as'] . '`';
                }
            }
            $select_sql = implode(', ', $select_sql);
        } else {
            $select_sql = '`' . $this->getDbTableName() . '`.*';
        }

        // Where
        $where_sql = $this->getWhereSql();
        $where_sql = $where_sql ? 'WHERE ' . $where_sql : '';

        // Having
        $having_sql = $this->getHavingSql();
        $having_sql = $having_sql ? 'HAVING ' . $having_sql : '';

        // Order by
        $order_by_sql = $this->getOrderBySQL();

        // Limit
        $limit_sql = $this->sql_limit ? 'LIMIT ' . $this->sql_offset . ', ' . $this->sql_limit : '';

        // Group by
        $group_sql = $this->getGroupBySql();

        // Joins
        $join_sql = $this->getJoinTablesSql();

        // Counting total
        if ($for_max_object_count) {
            $select_sql = 'COUNT(*)';
            $where_sql = '';
            $having_sql = '';
            $order_by_sql = '';
            $limit_sql = '';
        }

        $sql_calc_found_rows = $this->require_to_count_total_rows ? ' SQL_CALC_FOUND_ROWS ' : '';

        $sql = '
SELECT ' . $sql_calc_found_rows . $select_sql . '
FROM `' . $this->getDbTableName() . '`
' . $join_sql . '
' . $where_sql . '
' . $group_sql . '
' . $having_sql . '
' . $order_by_sql . '
' . $limit_sql . '
    ';

        return $sql;
    }

    public function getSelectFields(): array
    {
        return $this->sql_select_fields;
    }

    /**
     * @return string
     */
    private function getWhereSql(): string
    {
        $res = [];
        foreach ($this->getWhereFields() as $field_data) {
            if ($field_data['type'] === 'simple') {
                $res[] = '`' . $field_data['table'] . '`.`' . $field_data['field'] . '` = "' . SQL::sql_prepare($field_data['value']) . '"';
            } elseif ($field_data['type'] === 'string') {
                $res[] = $field_data['value'];
            }
        }

        return implode(' AND ', $res);
    }

    /**
     * @return array
     */
    private function getWhereFields(): array
    {
        return $this->sql_where_fields;
    }

    private function getHavingSql(): string
    {
        $res = [];
        foreach ($this->having_fields as $having) {
            $res[] = '`' . $having['field'] . '` ' . $having['value'];
        }

        return implode(' AND ', $res);
    }

    /**
     * @return string SQL string
     */
    private function getOrderBySQL(): string
    {
        if ($this->order_random) {
            return ' ORDER BY RAND()';
        }

        $order_by = [];
        foreach ($this->getOrderFields() as $field_data) {
            if ($field_data['type'] === 'simple') {
                $order_by[] = ($field_data['do_not_use_table_in_sql'] ? '' : '`' . $field_data['table'] . '`.') . '`' . $field_data['field'] . '` ' . $field_data['direction'];
            } elseif ($field_data['type'] === 'string') {
                $order_by[] = $field_data['field'];
            }
        }

        if ($order_by) {
            return ' ORDER BY ' . implode(', ', $order_by);
        }

        return '';
    }

    /**
     * @return array
     */
    private function getOrderFields(): array
    {
        return $this->order_fields;
    }

    protected function getGroupBySql(): string
    {
        $res = [];
        foreach ($this->group_by_fields as $group) {
            $res[] = '`' . $group['table'] . '`.`' . $group['field'] . '`';
        }
        if ($res) {
            return ' GROUP BY ' . implode(', ', $res);
        }

        return '';
    }

    public function getJoinTablesSql(): string
    {
        $sql = [];
        foreach ($this->join_tables as $table) {
            $sql[] = $table['type'] . ' JOIN `' . $table['table'] . '`' . ($table['alias'] !== $table['table'] ? ' AS `' . $table['alias'] . '`' : '') . ' ON (`' . $table['alias'] . '`.`' . $table['left'] . '` = `' . $table['right_table'] . '`.`' . $table['right'] . '`)';
        }

        return implode(' ', $sql);
    }

    /**
     * @param string $hash_string
     * @return string
     */
    private function getCacheKey($hash_string = ''): string
    {
        // Cache key = prefix + class name + unique session id (not obligate) + current created sql query
        return self::$_cache_key_prefix . md5(str_replace('\\', '_', get_class($this)) . '_' . $hash_string);
    }

    private function getObjectClass()
    {
        return substr(get_class($this), 0, -10); // Remove string "Collection" from name

    }

    /**
     * @return array
     */
    protected function getCollectedObjects(): array
    {
        return $this->collected_objects;
    }

    /**
     * Set collected objects in Repository - may be useful in mass-updates
     * @param array $objects
     * @return $this
     */
    public function setCollectedObjects(array $objects)
    {
        $this->collected_objects = $objects;

        return $this;
    }

    /**
     * Create one Entity by id
     * @param int $id
     * @return Entity
     */
    public static function findOneEntityById($id): Entity
    {
        return self::findOneEntityByCriteria(['id' => $id]);
    }

    /**
     * Return one Entity by array of criteria
     *
     * @param array $criteria select AND
     * @param array $exclude  select NOT
     *
     * @return Entity
     */
    public static function findOneEntityByCriteria(array $criteria = [], array $exclude = []): Entity
    {
        $class = static::class;

        /** @var EntityRepository $obj_collection */
        $obj_collection = new $class();
        $obj_collection->setLimit(1);

        foreach ($criteria as $k => $v) {
            $obj_collection->addSimpleWhereField($k, $v);
        }

        foreach ($exclude as $k => $v) {
            $obj_collection->addWhereFieldIsNot($k, $v);
        }

        return $obj_collection->getFirstObjectFromCollection();
    }

    /**
     * @param int $limit
     * @return $this
     */
    public function setLimit($limit)
    {
        $this->sql_limit = (int)$limit;

        return $this;
    }

    /**
     * @return Entity|NULL
     */
    public function getFirstObjectFromCollection()
    {
        $limit_tmp = $this->sql_limit;
        $this->setLimit(1);
        $res = NULL;

        foreach ($this->getAsArrayOfObjectData() as $obj_data) {

            $class = $this->getObjectClass();
            /** @var Entity $obj */
            $obj = new $class();
            $obj->loadDataFromArray($obj_data, true);

            $res = $obj;
            break;
        }

        $this->setLimit($limit_tmp);

        return $res;
    }

    /**
     * @param bool $non_iterator - do not use Iterator, may be useful for dumping output
     * @return iterable
     */
    public function getAsArrayOfObjectData($non_iterator = false)
    {
        if ($non_iterator) {
            $this->setGenerateOutputWithIterator(false);
        }

        $this->collectObjects(true);

        return $this->getCollectedData();
    }

    /**
     * @param bool $flag
     * @return $this
     */
    public function setGenerateOutputWithIterator($flag)
    {
        $this->use_iterator = $flag;

        return $this;
    }

    /**
     * @return iterable
     */
    protected function getCollectedData()
    {
        return $this->collected_objects_data;
    }

    public function deleteObjectCollection()
    {
        $this->collectObjects();

        // Call delete on every object
        foreach ($this->getCollectedObjects() as $v) {
            /** @var Entity $v */
            $v->deleteObject();
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getDbTableFields(): array
    {
        return SQL::getFields($this->getDbTableName());
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setWhereId($id)
    {
        $this->setIds([$id]);

        return $this;
    }

    public function getIds(): array
    {
        return array_values($this->getPairs('id'));
    }

    /**
     * @param string $value_field
     * @param string $key_field
     * @return array
     */
    public function getPairs($value_field, $key_field = 'id'): array
    {
        $ent = $this->getFirstObjectFromCollection();
        $key_method = 'get' . ucfirst($key_field);
        $value_method = 'get' . ucfirst($value_field);
        if (($key_method !== 'getId' && method_exists($ent, $key_method)) || method_exists($ent, $value_method)){
            $this->collectObjects();

            $pairs = [];
            foreach ($this->getAsArrayOfObjects() as $v) {
                /** @var Entity $v */
                $v->loadDataFromDB();

                $key_method = 'get' . ucfirst($key_field);
                $value_method = 'get' . ucfirst($value_field);
                $pairs[$v->{$key_method}()] = $v->{$value_method}();
            }
        } else {
            $this->addSimpleSelectFields([$key_field, $value_field]);
            $data = q_assoc($this->getSelectSql());
            $pairs = [];
            foreach ($data as $row){
                $pairs[$row[$key_field]] = $row[$value_field];
            }
        }

        return $pairs;
    }

    public function addSimpleSelectFields(array $fields, $table = false)
    {
        if (!$table) {
            $table = $this->getDbTableName();
        }

        foreach ($fields as $k => $field) {
            // Translation field
            if (in_array($field, $this->getTranslationFields(), true)) {
                ++$this->translation_join_count;
                $this->addJoinTable(['cms_translations', $this->getTranslationTableJoinAlias() . $this->translation_join_count], 'id', $field, 'LEFT', $table);

                $this->sql_select_fields[] = [
                    'table' => $this->getTranslationTableJoinAlias() . $this->translation_join_count . '',
                    'field' => $this->getLanguage(),
                    'as'    => $field,
                    'type'  => 'translation'
                ];
            } else {
                // Simple field
                $this->sql_select_fields[] = [
                    'table' => $table,
                    'field' => $field,
                    'as'    => false,
                    'type'  => 'simple'
                ];
            }
        }

        return $this;
    }

    public function getSumOfOneField($field): int
    {
        $sum = 0;

        foreach ($this->getAsArrayOfObjectData() as $v) {
            $sum += $v[$field];
        }

        return $sum;
    }

    public function addGroupBy($field, $table = '') {
        // No table provided
        if (!$table) {
            $table = $this->getDbTableName();
        }

        $this->group_by_fields[] = [
            'table' => $table,
            'field' => $field
        ];

        return $this;
    }

    public function addHaving($field, $value) {
        $this->having_fields[] = [
            'field' => $field,
            'value' => $value
        ];
    }

    public function flipBoolValue($field) {
        if (!$this->getCollectedObjects()) {
            $this->collectObjects(false, true);
        }

        foreach ($this->getCollectedObjects() as $object) {
            /** @var Entity $object */
            $object->flipBoolValue($field);
        }

        return $this;
    }

    public function save()
    {
        if (!$this->getCollectedObjects()) {
            $this->collectObjects(false, true);
        }

        foreach ($this->getCollectedObjects() as $object) {
            /** @var Entity $object */
            $object->save();
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function hasAnyObjectInCollection(): bool
    {
        $this->collectObjects(true);

        $obj = $this->getFirstObjectFromCollection();

        return (bool)$obj;
    }

    /**
     * @param int $count
     * @return bool
     */
    public function hasExactCountOfObjects($count): bool
    {
        $this->collectObjects(true);

        return (bool)((int)$this->getCountOfObjectsInCollection() === (int)$count);
    }

    /**
     * @return int
     */
    public function getCountOfObjectsInCollection(): int
    {
        $this->setGenerateOutputWithIterator(false);
        $this->collectObjects(true);

        return count($this->collected_objects_data);
    }

    /**
     * Remove all limits and where fields, and get total amount in collection
     * @return int
     */
    public function getCountOfMaxPossibleFoundObjectsWithoutFilters(): int
    {
        $this->setGenerateOutputWithIterator(false);

        return (int)q_value($this->getSelectSql(true));
    }

    /**
     * @return Entity
     */
    public function getLastObjectFromCollection(): Entity
    {
        $objects = $this->getAsArrayOfObjects();
        if ($objects) {
            return array_pop($objects);
        }

        return NULL;
    }

    /**
     * @param int $offset
     * @return $this
     */
    public function setOffset($offset) {
        $this->sql_offset = (int)$offset;

        return $this;
    }

    /**
     * If you need to add translation field on the fly, e.g. when merging repositories
     * @param string $field_name
     * @return $this
     */
    public function addTranslationFieldForAutoSelects($field_name) {
        $this->translation_fields[] = $field_name;

        return $this;
    }

    /**
     * @param string $field
     * @param bool   $direction_desc
     * @param string $table
     * @param bool $do_not_use_table_in_sql required in some conditions with temp fields
     * @return $this
     */
    public function addOrderByField($field = 'order', $direction_desc = false, $table = '', $do_not_use_table_in_sql = false) {
        // No table provided
        if (!$table) {
            $table = $this->getDbTableName();
        }

        $direction = $direction_desc ? 'DESC' : ' ASC';

        // Swap fields with translation table
        if (in_array($field, $this->translation_fields, true)) {
            $this->translation_join_count++;
            $this->addJoinTable(['cms_translations', $this->getTranslationTableJoinAlias() . $this->translation_join_count], 'id', $field, 'LEFT', $table);

            $this->order_fields[] = [
                'table'                   => false,
                'field'                   => '`' . $this->getTranslationTableJoinAlias() . $this->translation_join_count . '`.`' . $this->getLanguage() . '`',
                'direction'               => $direction,
                'do_not_use_table_in_sql' => true,
                'type'                    => 'string'
            ];
        } else {
            $this->order_fields[] = [
                'table'                   => $table,
                'field'                   => $field,
                'direction'               => $direction,
                'do_not_use_table_in_sql' => $do_not_use_table_in_sql,
                'type'                    => 'simple'
            ];
        }

        return $this;
    }

    public function addOrderByFieldAsString($sql) {
        $this->order_fields[] = [
            'table'                   => false,
            'field'                   => $sql,
            'direction'               => false,
            'do_not_use_table_in_sql' => true,
            'type'                    => 'string'
        ];

        return $this;
    }

    /**
     * @param $searchable_string
     * @param $field
     * @param string $table
     * @return $this
     */
    public function addOrderByLocate($searchable_string, $field , $table = '') {
        // No table provided
        if (!$table) {
            $table = $this->getDbTableName();
        }

        $this->order_fields[] = [
            'table' => $table,
            'type'  => 'string',
            'field' => 'LOCATE ("'. SQL::sql_prepare($searchable_string) .'", `'. $table .'`.`'. $field .'`)'
        ];

        return $this;
    }

    /**
     * @param bool
     * @return $this
     */
    public function setOrderByRandom($flag) {
        $this->order_random = $flag;

        return $this;
    }

    public function clearCollectionCache() {
        $this->last_used_sql = '';

        return $this;
    }

    public function addSimpleSelectFieldsAsAlias($field, $alias, $table = false) {
        if (!$table) {
            $table = $this->getDbTableName();
        }
        // Translation field
        if (in_array($field, $this->getTranslationFields(), true)) {
            ++$this->translation_join_count;
            $this->addJoinTable(['cms_translations', $this->getTranslationTableJoinAlias() . $this->translation_join_count], 'id', $field, 'LEFT', $table);

            $this->sql_select_fields[] = [
                'table' => $this->getTranslationTableJoinAlias() . $this->translation_join_count . '',
                'field' => $this->getLanguage(),
                'as'    => $alias,
                'type'  => 'translation'
            ];
        } else {
            $this->sql_select_fields[] = [
                'table' => $table,
                'field' => $field,
                'as'    => $alias,
                'type'  => 'simple'
            ];
        }

        return $this;
    }

    public function addSimpleSelectFieldsAsString($sql) {
        $this->sql_select_fields[] = [
            'table' => false,
            'field' => $sql,
            'as'    => false,
            'type'  => 'string'
        ];

        return $this;
    }

    public function addSelectCountFromPairedObject(EntityRepository $repository, $field_name, $count_by_field) {
        $this->sql_select_fields[] = [
            'table' => false,
            'field' => '(SELECT COUNT(*) FROM `'. $repository->getDbTableName() .'` WHERE `'. $repository->getDbTableName() .'`.`'. $count_by_field .'` = `'. $this->getDbTableName() .'`.`id`) AS `'. $field_name .'`',
            'as'    => false,
            'type'  => 'string'
        ];

        return $this;
    }

    /**
     * Select count(*)
     * @return int
     */
    public function getTotalSelectedRowsWithoutLimit(): int
    {
        if (!$this->require_to_count_total_rows) {
            dump('You need to call setRequireCountRowsWithoutLimits(true) before requesting result');
        }

        $this->collectObjects();

        return $this->total_count_rows;
    }

    public function setRequireCountRowsWithoutLimits($flag): bool
    {
        return $this->require_to_count_total_rows = (bool)$flag;
    }

    public function enableDebug()
    {
        $this->debug = true;

        return $this;
    }

    /**
     * @param $name
     * @param $args
     * @return string
     */
    public function __call($name, $args) {
        // Check which method was called
        if (strpos($name, 'setWhere') === 0) { // setWhere... for filtering

            $param = substr($name, 8);  // Cut "setWhere"
            $param = Converter::from_camel_case($param);

            // Check maybe arg supplied is Entity - than we have to call EntityId
            if (isset($args[0]) && $args[0] instanceof Entity) {
                /** @var Entity $obj */
                $obj = $args[0];
                $method_name = 'get' . ucfirst($param);
                $args[0] = $obj->$method_name();
            }

            // Emulate setWhereSomething($k, $v);
            $this->addSimpleWhereField($param, $args[0] ?? NULL);

        } elseif (strpos($name, 'set') === 0) { // set{Field} for every object in repository

            // Collect objects
            if (!$this->getCollectedObjects()) {
                $this->collectObjects(false, true);
            }

            // Check maybe arg supplied is Entity - than we have to call EntityId
            if (isset($args[0]) && $args[0] instanceof Entity) {
                /** @var Entity $obj */
                $obj = $args[0];
                $args[0] = $obj->$name();
            }

            // Set field in every inner object
            foreach ($this->getCollectedObjects() as $object) {
                /** @var Entity $object */
                $object->{$name}($args[0] ?? NULL);
            }

        } else {
            dump('Method "' . $name . '" unknown in class "' . get_class($this) . '"');
        }

        return $this;
    }

    /**
     * @param string $db_table
     * @return $this
     */
    public function setDbTableName($db_table) {
        $this->db_table = $db_table;

        return $this;
    }

    /**
     * @param int $ttl
     * @return $this
     */
    public function enableUsingCache($ttl = 600)
    {
        // Disable iterator because we need to save full array data
        $this->setGenerateOutputWithIterator(false);
        $this->cache_ttl = $ttl;
        $this->use_cache = true;

        return $this;
    }

    /**
     * @param bool $download_as_file
     * @return string
     */
    public function exportAsSerializedData($download_as_file = false): string
    {
        if (!$this->getCollectedObjects()) {
            $this->collectObjects(false, true);
        }

        $objects = [];
        $object = NULL;
        foreach ($this->getCollectedObjects() as $object) {
            /** @var Entity $object */
            $objects[] = $object;
        }

        if (!$objects) {
            error('No Objects selected');
        }

        $data = [];
        $data['objects'] = serialize($objects);
        $data['class'] = Converter::getPathToClassFile($object);
        $data['class'] = str_replace(DIR_BASE, '', $data['class']);

        $data = serialize($data);

        if (!$download_as_file) {
            return $data;
        }

        FileSystem::streamOutput(Converter::classWithNamespaceToUnqualifiedShort($object) . '.cms_obj', $data);

        return $data;
    }

    /**
     * @param EntityRepository $collection
     * @param string $join_on_key in current collection to join another collection on ID
     * @param string $join_index - main index foreign key
     * @param string $join_type INNER|LEFT
     * @return $this
     */
    public function mergeWithCollection(EntityRepository $collection, $join_on_key, $join_index = 'id', $join_type = 'INNER')
    {
        $this->addJoinTable($collection->getDbTableName(), $join_index, $join_on_key, $join_type);
        $this->mergeCollectionSqlSelectWithAnotherCollection($collection);

        return $this;
    }

    private function mergeCollectionSqlSelectWithAnotherCollection(EntityRepository $collection)
    {
        $select_fields = $collection->getSelectFields();
        foreach ($select_fields as $select_field) {
            $this->sql_select_fields[] = $select_field;
        }

        $where_fields = $collection->getWhereFields();
        foreach ($where_fields as $where_field) {
            $this->sql_where_fields[] = $where_field;
        }

        $group_fields = $collection->getGroupByField();
        foreach ($group_fields as $group_field) {
            $this->group_by_fields[] = $group_field;
        }

        $having_fields = $collection->getHavingFields();
        foreach ($having_fields as $having_field) {
            $this->having_fields[] = $having_field;
        }

        $order_fields = $collection->getOrderFields();
        foreach ($order_fields as $order_field) {
            $this->order_fields[] = $order_field;
        }

        $translation_fields = $collection->getTranslationFields();
        foreach ($translation_fields as $translation_field) {
            $this->translation_fields[] = $translation_field;
        }

        $join_tables = $collection->getJoinTables();
        foreach ($join_tables as $join_table) {
            $this->addJoinTable([$join_table['table'], $join_table['alias']], $join_table['left'], $join_table['right'], $join_table['type'], $join_table['right_table']);
        }

        return $this;
    }

    protected function getGroupByField(): array
    {
        return $this->group_by_fields;
    }

    private function getHavingFields(): array
    {
        return $this->having_fields;
    }

    /**
     * @return array
     */
    private function getJoinTables(): array
    {
        return $this->join_tables;
    }

    /**
     * Filter collection by value exclusive
     * @param $field
     * @param array $values
     * @param string $table
     * @return $this
     */
    public function addWhereFieldNotIn($field, array $values, $table = '')
    {
        if (!$table) {
            $table = $this->getDbTableName();
        }

        if (!$values) {
            $values = [NULL];
        }
        foreach ($values as $k => & $v) {
            $v = sql_prepare($v);
        }
        unset($v);

        $this->addWhereFieldAsString('`'. $table .'`.`'. $field .'` NOT IN ("'. implode('", "', $values) .'")');

        return $this;
    }

    /**
     * @param $field
     * @param string $value
     * @param string $table
     * @return $this
     */
    public function addWhereFieldIsLower($field, $value, $table = '')
    {
        if (!$table) {
            $table = $this->getDbTableName();
        }

        $value = sql_prepare($value);

        $this->addWhereFieldAsString('`'. $table .'`.`'. $field .'` < "'. $value .'"');

        return $this;
    }

    /**
     * @param $field
     * @param string $value
     * @param string $table
     * @return $this
     */
    public function addWhereFieldIsLowerOrEqual($field, $value, $table = '')
    {
        if (!$table) {
            $table = $this->getDbTableName();
        }

        $value = sql_prepare($value);

        $this->addWhereFieldAsString('`'. $table .'`.`'. $field .'` <= "'. $value .'"');

        return $this;
    }

    /**
     * @param $field
     * @param string $value
     * @param string $table
     * @return $this
     */
    public function addWhereFieldIsHigher($field, $value, $table = '')
    {
        if (!$table) {
            $table = $this->getDbTableName();
        }

        $value = sql_prepare($value);

        $this->addWhereFieldAsString('`'. $table .'`.`'. $field .'` > "'. $value .'"');

        return $this;
    }

    // Reset auto_increment to 1

    /**
     * @param $field
     * @param string $value
     * @param string $table
     * @return $this
     */
    public function addWhereFieldIsHigherOrEqual($field, $value, $table = '')
    {
        if (!$table) {
            $table = $this->getDbTableName();
        }

        $value = sql_prepare($value);

        $this->addWhereFieldAsString('`' . $table . '`.`' . $field . '` >= "' . sql_prepare($value) . '"');

        return $this;
    }



    /* STATIC ALIASES */

    /**
     * Filter collection by value inclusive
     * @param $fields - value or array of values, WHERE sentence uses OR between values in one array
     * @param string $like_value
     * @param bool   $left_any
     * @param bool   $right_any
     * @param string $table
     * @return $this
     */
    public function addWhereFieldIsLike($fields, $like_value, $left_any = true, $right_any = true, $table = '')
    {
        $fields = (array)$fields;

        if (!$table) {
            $table = $this->getDbTableName();
        }

        // If not translation field present
        $result_table = $table;

        $sql = [];

        // All fields glued with OR
        foreach ($fields as $field) {
            if (in_array($field, $this->translation_fields, true)) {
                ++$this->translation_join_count;
                $this->addJoinTable(['cms_translations', $this->getTranslationTableJoinAlias() . $this->translation_join_count], 'id', $field, 'LEFT', $table);

                // Set real used table an field instead of requested
                $result_table = $this->getTranslationTableJoinAlias() . $this->translation_join_count;
                $field = $this->getLanguage();
            }

            $sql[] = '`'. $result_table .'`.`'. $field .'` LIKE "'. ($left_any ? '%' : '') . sql_prepare($like_value, true) . ($right_any ? '%' : '') .'"';

        }

        // Make one big WHERE
        $sql = implode(' OR ', $sql);

        $this->addWhereFieldAsString('('. $sql .')');

        return $this;
    }

    /**
     * Filter collection by value exclusive
     * @param $field
     * @param string $like_value
     * @param bool $left_any
     * @param bool $right_any
     * @param string $table
     * @return $this
     */
    public function addWhereFieldIsNotLike($field, $like_value, $left_any = true, $right_any = true, $table = '')
    {
        if (!$table) {
            $table = $this->getDbTableName();
        }

        $this->addWhereFieldAsString('`'. $table .'`.`'. $field .'` NOT LIKE "'. ($left_any ? '%' : '') . sql_prepare($like_value, true) . ($right_any ? '%' : '') .'"');

        return $this;
    }

    public function alterTableResetAutoIncrement()
    {
        $schema = new TableStructure();
        $schema->setTableName($this->getDbTableName());
        $schema->resetAutoIncrement();

        return $this;
    }

    /**
     * Retrieve an external iterator
     * @link  http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator(): \Traversable
    {
        return new ArrayIterator($this->getAsArrayOfObjects());
    }

    /**
     * @param $lng
     * @return $this
     */
    public function setLanguage($lng){
        $this->lng = $lng;
        return $this;
    }

    /**
     * @param mixed $data
     * @param int   $serialize
     * @param int   $clean
     */
    protected function debug($data, $serialize = 0, $clean = 1)
    {
        if (!$this->debug) return;

        dump($data, $serialize, $clean);
    }
}