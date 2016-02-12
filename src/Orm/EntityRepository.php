<?php

namespace TMCms\Orm;

use TMCms\Cache\Cacher;
use TMCms\Config\Settings;
use TMCms\DB\SQL;
use TMCms\Files\FileSystem;
use TMCms\Strings\Converter;

class EntityRepository {
    protected $db_table = ''; // Should be overwritten in extended class
    protected $translation_fields = []; // Should be overwritten in extended class
    protected $table_structure = []; // Should be overwitten in extended class

    private static $_cache_key_prefix = 'orm_entity_repository_';

    private $sql_where_fields = [];
    private $sql_select_fields = [];
    private $sql_offset = 0;
    private $sql_limit = 0;
    private $order_fields = [];
    private $order_random = false;
    private $group_by_fields = [];
    private $having_fields = [];
    private $translation_joins = [];

    private $use_iterator = true;
    private $collected_objects = [];
    private $collected_objects_data = [];

    private $total_count_rows;
    private $require_to_count_total_rows = false;

    protected $debug = false;
    private $use_cache = false;
    private $cache_ttl = 60;

    private $join_tables = [];
    private $last_used_sql;

    public function __construct($ids = []) {
        if (!Settings::isProductionState()) {
            // Create or update table
            $this->ensureDbTableExists();
        }

        if ($ids) {
            $this->setIds($ids);
        }

        return $this;
    }

    public static function getInstance($ids = []) {
        return new static($ids);
    }

    public function deleteObjectCollection() {
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
    public function getDbTableFields()
    {
        return SQL::getFields($this->getDbTableName());
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

                if (!isset($v['id'])) {
                    SQL::getInstance()->addPrimaryAutoIncrementIdFieldToTable($this->getDbTableName());
                    dump('No ID field found for ' . get_class($this) . '. Field "id" with auto-increment was created in table "'. $this->getDbTableName() .'". Please reload page.');
                }

                // Prevent auto-query db, skip tables with no id field
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
                'collected_objects' => $this->collected_objects
            ];
            Cacher::getInstance()->getDefaultCacher()->set($this->getCacheKey($sql), $data, $this->cache_ttl);
        }

        return $this;
    }

    protected function getCollectedObjects()
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

    protected function getCollectedData()
    {
        return $this->collected_objects_data;
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
     * @param int $id
     * @return $this
     */
    public function setWhereId($id)
    {
        $this->setIds([$id]);

        return $this;
    }

    public function getIds() {
        $ids = [];

        foreach ($this->getAsArrayOfObjectData() as $v) {
            $ids[] = $v['id'];
        }

        return $ids;
    }

    public function getSumOfOneField($field) {
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
    public function hasAnyObjectInCollection()
    {
        $this->collectObjects(true);

        $obj = $this->getFirstObjectFromCollection();

        return (bool)$obj;
    }

    /**
     * @param int $count
     * @return bool
     */
    public function hasExactCountOfObjects($count)
    {
        $this->collectObjects(true);

        return (bool)((int)$this->getCountOfObjectsInCollection() === (int)$count);
    }

    /**
     * @return int
     */
    public function getCountOfObjectsInCollection()
    {
        $this->collectObjects(true);

        return count($this->collected_objects_data);
    }

    /**
     * @return bool
     */
    public function getCountOfMaxPossibleFoundObjectsWithoutFilters()
    {
        return (int)q_value($this->getSelectSql(true));
    }

    /**
     * @return Entity
     */
    public function getFirstObjectFromCollection()
    {
        $limit_tmp = $this->sql_limit;
        $this->setLimit(1);

        foreach ($this->getAsArrayOfObjectData() as $obj_data) {
            $this->setLimit($limit_tmp);

            $class = $this->getObjectClass();
            /** @var Entity $obj */
            $obj = new $class();
            $obj->loadDataFromArray($obj_data, true);

            return $obj;
        }

        $this->setLimit($limit_tmp);
        return NULL;
    }

    /**
     * @return Entity
     */
    public function getLastObjectFromCollection()
    {
        $objects = $this->getAsArrayOfObjects();
        if ($objects) {
            return array_pop($objects);
        }

        return NULL;
    }

    public function getAsArrayOfObjects()
    {
        $this->collectObjects();

        return $this->getCollectedObjects();
    }

    /**
     * @param bool $non_iterator - do not use Iterator, may be usefull for dumping output
     * @return array
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
     * @param string $value_field
     * @param string $key_field
     * @return array
     */
    public function getPairs($value_field, $key_field = '')
    {
        if (!$key_field) {
            $key_field = 'id';
        }

        $this->collectObjects();

        $pairs = [];
        foreach ($this->getAsArrayOfObjects() as $v) {
            /** @var Entity $v */
            $v->loadDataFromDB();

            $key_method = 'get' . ucfirst($key_field);
            $value_method = 'get' . ucfirst($value_field);
            $pairs[$v->{$key_method}()] = $v->{$value_method}();
        }

        return $pairs;
    }

    /**
     * @param int $limit
     * @return $this
     */
    public function setLimit($limit) {
        $this->sql_limit = (int)$limit;

        return $this;
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
     * @param string $field
     * @param bool $direction_desc
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


        if (in_array($field, $this->translation_fields)) {
            $k = count($this->translation_joins);
            $this->translation_joins[] = 'LEFT JOIN `cms_translations` AS `d' . $k . '` ON (`d' . $k . '`.`id` = `'. $table .'`.`' . $field . '`)';

            $this->order_fields[] = [
                'table' => false,
                'field' => '`d' . $k . '`.`' . LNG . '`',
                'direction' => $direction,
                'do_not_use_table_in_sql' => true,
                'type' => 'string'
            ];
        } else {
            $this->order_fields[] = [
                'table' => $table,
                'field' => $field,
                'direction' => $direction,
                'do_not_use_table_in_sql' => $do_not_use_table_in_sql,
                'type' => 'simple'
            ];
        }

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
            'type' => 'string',
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

    public function addSimpleSelectFields(array $fields, $table = false) {
        if (!$table) {
            $table = $this->getDbTableName();
        }

        foreach ($fields as $k => $field) {
            $this->sql_select_fields[] = [
                'table' => $table,
                'field' => $field,
                'as' => false,
                'type' => 'simple'
            ];
        }

        return $this;
    }

    public function addSimpleSelectFieldsAsAlias($field, $alias, $table = false) {
        if (!$table) {
            $table = $this->getDbTableName();
        }

        $this->sql_select_fields[] = [
            'table' => $table,
            'field' => $field,
            'as' => $alias,
            'type' => 'simple'
        ];

        return $this;
    }

    public function addSimpleSelectFieldsAsString($sql) {
        $this->sql_select_fields[] = [
            'table' => false,
            'field' => $sql,
            'as' => false,
            'type' => 'string'
        ];

        return $this;
    }

    public function addSelectCountFromPairedObject(EntityRepository $repository, $field_name, $count_by_field) {
        $this->sql_select_fields[] = [
            'table' => false,
            'field' => '(SELECT COUNT(*) FROM `'. $repository->getDbTableName() .'` WHERE `'. $repository->getDbTableName() .'`.`'. $count_by_field .'` = `'. $this->getDbTableName() .'`.`id`) AS `'. $field_name .'`',
            'as' => false,
            'type' => 'string'
        ];

        return $this;
    }

    public function getSelectFields() {
        return $this->sql_select_fields;
    }

    public function getTotalSelectedRowsWithoutLimit() {
        return $this->total_count_rows;
    }

    public function setRequireCountRowsWithoutLimits($flag) {
        return $this->require_to_count_total_rows = (bool)$flag;
    }

    public function getSelectSql($for_max_object_count = false)
    {
        // Select
        if ($this->sql_select_fields) {
            $select_sql = [];
            foreach ($this->sql_select_fields as $field_data) {
                // Simple select
                if ($field_data['type'] == 'simple') {
                    $select_sql[] = '`' . $field_data['table'] . '`.`' . $field_data['field'] . '`' . ($field_data['as'] ? ' AS `'. $field_data['as'] .'`' : '');
                } elseif ($field_data['type'] == 'string') {
                    $select_sql[] = $field_data['field'];
                }
            }
            $select_sql = implode(', ', $select_sql);
        } else {
            $select_sql = '`'. $this->getDbTableName() .'`.*';
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

        // Translations
        $translation_sql = implode(', ', $this->getTranslationJoinTables());

        // Counting total
        if ($for_max_object_count) {
            $select_sql = 'COUNT(*)';
            $translation_sql = '';
            $where_sql = '';
            $having_sql = '';
            $order_by_sql = '';
            $limit_sql = '';
        }

        $sql_calc_found_rows = $this->require_to_count_total_rows ? ' SQL_CALC_FOUND_ROWS ' : '';

        $sql = '
SELECT '. $sql_calc_found_rows . $select_sql .'
FROM `'. $this->getDbTableName() .'`
'. $translation_sql .'
'. $join_sql .'
'. $where_sql .'
'. $group_sql .'
'. $having_sql .'
'. $order_by_sql .'
'. $limit_sql .'
    ';

        return $sql;
    }

    /**
     * @return string SQL string
     */
    private function getOrderBySQL() {
        if ($this->order_random) {
            return ' ORDER BY RAND()';
        }

        $order_by = [];
        foreach ($this->getOrderFields() as $field_data) {
            if ($field_data['type'] == 'simple') {
                $order_by[] = ($field_data['do_not_use_table_in_sql'] ? '' : '`'. $field_data['table'] .'`.') . '`'. $field_data['field'] .'` '. $field_data['direction'];
            } elseif ($field_data['type'] == 'string') {
                $order_by[] = $field_data['field'];
            }
        }

        if ($order_by) {
            return ' ORDER BY ' . implode(', ', $order_by);
        }

        return '';
    }

    /**
     * @param mixed $data
     * @param int $serialize
     * @param int $clean
     */
    protected function debug($data, $serialize = 0, $clean = 1)
    {
        if (!$this->debug) return;

        dump($data, $serialize, $clean);
    }

    public function enableDebug()
    {
        $this->debug = true;

        return $this;
    }

    private function getObjectClass() {
        // Create object for entity
        $obj_class = substr(get_class($this), 0, -10); // Remove string "Collection" from name

        return $obj_class;
    }

    /**
     * @param $name
     * @param $args
     * @return string
     */
    public function __call($name, $args) {
        // Check which method was called
        if (substr($name, 0, 8) == 'setWhere') { // setWhere... for filtering

            $param = substr($name, 8);  // Cut "setWhere"
            $param = Converter::from_camel_case($param);

            // Check maybe arg supplied is Entity - than we have to call EntityId
            if (isset($args[0]) && $args[0] instanceof Entity) {
                $param .= $param . '_id';
            }

            // Emulate setWhereSomething($k, $v);
            $this->addSimpleWhereField($param, isset($args[0]) ? $args[0] : NULL);

        } elseif (substr($name, 0, 3) == 'set') { // set{Field} for every object in repository

            // Collect objects
            if (!$this->getCollectedObjects()) {
                $this->collectObjects(false, true);
            }

            // Set field in every inner object
            foreach ($this->getCollectedObjects() as $object) {
                /** @var Entity $object */
                $object->{$name}(isset($args[0]) ? $args[0] : NULL);
            }

        } else {
            dump('Method "'. $name .'" unknown');
        }

        return $this;
    }

    private function mergeCollectionSqlSelectWithAnotherCollection(EntityRepository $collection) {
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

        $join_tables = $collection->getJoinTables();
        foreach ($join_tables as $join_table) {
            $this->addJoinTable($join_table['table'], $join_table['left'], $join_table['right'], $join_table['type']);
        }

        return $this;
    }

    public function addJoinTable($table, $on_left, $on_right, $type = '') {
        $this->join_tables[] = [
            'table' => $table,
            'left' => $on_left,
            'right' => $on_right,
            'type' => $type
        ];

        return $this;
    }

    public function getJoinTablesSql() {
        $sql = [];
        foreach ($this->join_tables as $table) {
            $sql[] = $table['type'] .' JOIN `'. $table['table'] .'` ON (`'. $table['table'] .'`.`'. $table['left'] .'` = `'. $this->getDbTableName() .'`.`' . $table['right'] . '`)';
        }

        return implode(' ', $sql);
    }

    /**
     * Return name in class or try to get from class name
     * @return string
     */
    public function getDbTableName() {
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

    /**
     * @param string $db_table
     * @return $this
     */
    public function setDbTableName($db_table) {
        $this->db_table = $db_table;

        return $this;
    }

    /**
     * @param bool $flag
     * @return $this
     */
    public function setGenerateOutputWithIterator($flag) {
        $this->use_iterator = $flag;

        return $this;
    }

    /**
     * @param string $hash_string
     * @return string
     */
    private function getCacheKey($hash_string = '') {
        // Cache key = prefix + class name + unique session id (not obligate) + current created sql query
        return self::$_cache_key_prefix . md5(str_replace('\\', '_', get_class($this)) . '_' . $hash_string);
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
     * @return array
     */
    private function getWhereFields()
    {
        return $this->sql_where_fields;
    }

    /**
     * @return string
     */
    private function getWhereSql()
    {
        $res = [];
        foreach ($this->getWhereFields() as $field_data) {
            if ($field_data['type'] == 'simple') {
                $res[] = '`'. $field_data['table'] .'`.`'. $field_data['field'] .'` = "'. SQL::sql_prepare($field_data['value']) .'"';
            } elseif ($field_data['type'] == 'string') {
                $res[] = $field_data['value'];
            }
        }

        return implode(' AND ', $res);
    }

    /**
     * @return array
     */
    private function getOrderFields()
    {
        return $this->order_fields;
    }

    /**
     * @return array
     */
    private function getJoinTables()
    {
        return $this->join_tables;
    }

    /**
     * @return array
     */
    private function getTranslationJoinTables()
    {
        return $this->translation_joins;
    }

    /**
     * @param bool $download_as_file
     * @return string
     */
    public function exportAsSerializedData($download_as_file = false)
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
        $this->mergeCollectionSqlSelectWithAnotherCollection($collection);
        $this->addJoinTable($collection->getDbTableName(), $join_index, $join_on_key, $join_type);

        return $this;
    }

    private function getHavingSql()
    {
        $res = [];
        foreach ($this->having_fields as $having) {
            $res[] = '`' . $having['field'] . '` ' . $having['value'];
        }
        return implode(' AND ', $res);
    }

    private function getHavingFields()
    {
        return $this->having_fields;
    }

    /**
     * @param string $field
     * @param string $value
     * @param string $table
     * @return $this
     */
    protected function addSimpleWhereField($field, $value = '', $table = '') {// No table provided
        if (!$table) {
            $table = $this->getDbTableName();
        }

        $this->sql_where_fields[] = [
            'table' => $table,
            'field' => $field,
            'value' => $value,
            'type' => 'simple'
        ];

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

        $this->addWhereFieldAsString('`'. $table .'`.`'. $field .'` IN ("'. implode('", "', $values) .'")');

        return $this;
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

        $this->addWhereFieldAsString('`'. $table .'`.`'. $field .'` != "' . sql_prepare($value)  .'"');

        return $this;
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
    public function addWhereFieldIsHigher($field, $value, $table = '')
    {
        if (!$table) {
            $table = $this->getDbTableName();
        }

        $value = sql_prepare($value);

        $this->addWhereFieldAsString('`'. $table .'`.`'. $field .'` > "'. $value .'"');

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
    public function addWhereFieldIsLike($field, $like_value, $left_any = true, $right_any = true, $table = '')
    {
        if (!$table) {
            $table = $this->getDbTableName();
        }

        $this->addWhereFieldAsString('`'. $table .'`.`'. $field .'` LIKE "'. ($left_any ? '%' : '') . sql_prepare($like_value, true) . ($right_any ? '%' : '') .'"');

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

    public function addWhereFieldAsString($sql) {
        $this->sql_where_fields[] = [
            'table' => false,
            'field' => false,
            'value' => $sql,
            'type' => 'string'
        ];

        return $this;
    }

    protected function getGroupBySql()
    {
        $res = [];
        foreach ($this->group_by_fields as $group) {
            $res[] = '`' . $group['table'] . '`.`' . $group['field'] .'`';
        }
        if ($res) {
            return ' GROUP BY ' . implode(', ', $res);
        }

        return '';
    }

    protected function getGroupByField()
    {
        return $this->group_by_fields;
    }

    /**
     * @return bool table exists
     */
    private function ensureDbTableExists() {
        $table = $this->getDbTableName();
        // May be empty
        if ($table == 'm_s') {
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

    protected function getTableStructure() {
        return $this->table_structure;
    }

    // Reset auto_increment to 1
    public function alterTableResetAutoIncrement()
    {
        $schema = new TableStructure();
        $schema->setTableName($this->getDbTableName());
        $schema->resetAutoIncrement();

        return $this;
    }



    /* STATIC ALIASES */

    /**
     * Return one Entity by array of criteria
     * @param array $criteria
     * @return Entity
     */
    public static function findOneEntityByCriteria(array $criteria) {
        $class = static::class;

        /** @var EntityRepository $obj_collection */
        $obj_collection = new $class();
        $obj_collection->setLimit(1);
        foreach ($criteria as $k => $v) {
            $method = 'setWhere' . Converter::to_camel_case($k);
            $obj_collection->{$method}($v);
        }
        return $obj_collection->getFirstObjectFromCollection();
    }

    /**
     * Return array of Entity by array of criteria
     * @param array $criteria
     * @return array
     */
    public static function findAllEntitiesByCriteria(array $criteria) {
        $class = static::class;

        /** @var EntityRepository $obj_collection */
        $obj_collection = new $class();
        foreach ($criteria as $k => $v) {
            $method = 'setWhere' . Converter::to_camel_case($k);
            $obj_collection->{$method}($v);
        }
        return $obj_collection->getAsArrayOfObjects();
    }

    /**
     * Create one Entity by id
     * @param int $id
     * @return Entity
     */
    public static function findOneEntityById($id) {
        return self::findOneEntityByCriteria(['id' => $id]);
    }
}