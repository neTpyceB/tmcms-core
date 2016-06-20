<?php

namespace TMCms\Orm;

use TMCms\Cache\Cacher;
use TMCms\Config\Configuration;
use TMCms\Config\Settings;
use TMCms\DB\SQL;
use TMCms\Strings\Converter;
use TMCms\Strings\SimpleCrypto;
use TMCms\Strings\Translations;

class Entity {
    protected $db_table = ''; // Should be overwritten in extended class
    protected $translation_fields = []; // Should be overwritten in extended class

    private static $_cache_key_prefix = 'orm_entity_';
    protected $encrypted_fields = [];

    private $data = [];
    private $translation_data = [];

    protected $changed_fields_for_update = [];
    protected $update_on_duplicate = false;

    public $debug = false;

    private $insert_low_priority = false;
    private $insert_delayed = false;
    private $encode_special_chars_for_html = false; // Auto use of htmlspecialchar for output
    private $field_callbacks = [];
    private static $encryption_key; // Key used to encrypt and decrypt db data

    public function __construct($id = 0, $load_from_db = true) {
        $this->data['id'] = NULL;

        if ($id) {
            $this->setId($id, $load_from_db);
        }

        return $this;
    }

    public static function getInstance($id = 0, $load_from_db = true) {
        return new static($id, $load_from_db);
    }

    /**
     * Change bool value to opposite
     * @param $field
     * @return $this
     */
    public function flipBoolValue($field) {
        $this->setField($field, !$this->getfield($field));

        return $this;
    }

    /**
     * Removed field from object
     * @param string|array $fields
     * @return $this
     */
    public function unsetField($fields) {
        $fields = (array)$fields;
        foreach ($fields as $field) {
            unset($this->data[$field]);
        }

        return $this;
    }

    /**
     * Removed field from object
     * @param string $field_from
     * @param string $field_to
     * @param bool $remove_renamed_field set false if need to keep both field
     * @return $this
     */
    public function renameField($field_from, $field_to, $remove_renamed_field = true) {
        $this->setField($field_to, $this->getField($field_from));
        if ($remove_renamed_field) {
            $this->unsetField($field_from);
        }

        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     * @param bool $skip_changed_fields
     * @return $this
     */
    public function setField($key, $value, $skip_changed_fields = false)
    {
        // Optimize updates - only required fields for updating
        if (!$skip_changed_fields) {
            $this->changed_fields_for_update[$key] = true;
        }

        if (in_array($key, $this->translation_fields) && !ctype_digit($value)) {
            // Saving Translation ID
            $this->translation_data[$key] = $value;
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->data['id'];
    }

    /**
     * @param int $id
     * @param bool $load_from_db
     * @return $this
     */
    public function setId($id, $load_from_db = true) {
        $this->data['id'] = $id;

        if ($load_from_db) {
            $this->loadDataFromDB();
        }

        return $this;
    }

    /**
     * @param string $field
     * @return mixed
     */
    public function getField($field)
    {
        $res = NULL;

        if (isset($this->data[$field]) || isset($this->translation_data[$field])) {
            if (in_array($field, $this->translation_fields)) {
                if (isset($this->translation_data[$field][LNG])) {
                    return $this->translation_data[$field][LNG];
                } elseif (isset($this->data[$field])) {
                    return Translations::get($this->data[$field], LNG);
                }
            }

            if (isset($this->data[$field])) {
                $res = $this->data[$field];
            }

            foreach ($this->field_callbacks as $callback) {
                $res = $callback($field, $res);
            }

            if ($this->encode_special_chars_for_html && is_scalar($res)) {
                $res = htmlspecialchars($res);
            }

            return $res;
        }

        return $res;
    }

    public function deleteObject() {
        $this->beforeDelete();

        SQL::delete($this->getDbTableName(), $this->getId());

        $this->deleteObjectDataFromCache();

        $this->afterDelete();

        return $this;
    }

    /**
     * @return bool loaded or not
     */
    public function deleteObjectDataFromCache()
    {
        Cacher::getInstance()->getDefaultCacher()->delete($this->getCacheKey());

        return $this;
    }

    /**
     * @return string
     */
    private function getCacheKey() {
        return self::$_cache_key_prefix . str_replace('\\', '_', get_class($this)) . '_' . $this->getId();
    }

    public function save() {
        $this->deleteObjectDataFromCache();

        $this->beforeSave();

        if ($this->encrypted_fields) {
            $this->encryptValues();
        }

        if ($this->getId()) {
            $this->update();
        } else {
            $this->create();
        }

        $this->afterSave();

        return $this;
    }

    private function update()
    {
        $this->beforeUpdate();

        // Start transaction - we have inner-translation updates
        SQL::startTransaction();

        $data = [];

        // Get updated fields
        $fields = SQL::getFields($this->getDbTableName());

        // Generate data to be updated
        foreach ($fields as $v) {
            // Update only changed fields
            if (isset($this->changed_fields_for_update[$v])) {

                // Translation field
                if (in_array($v, $this->translation_fields) && isset($this->translation_data[$v]) && is_array($this->translation_data[$v])) {
                    $data[$v] = Translations::update($this->translation_data[$v], $this->data[$v]);
                } else {
                    // Usual field
                    if ($this->getField($v) !== NULL) {
                        $data[$v] = $this->getField($v);
                    }
                }
            }
        }

        // Update entry in DB
        SQL::update($this->getDbTableName(), $data, $this->getId(), 'id', $this->insert_low_priority);

        // Load all fresh data from DB
        $this->loadDataFromDB();

        // Close all update queries
        SQL::confirmTransaction();

        $this->afterUpdate();

        return $this;
    }

    public function loadDataFromDB($id = 0) {
        if ($id) {
            $this->setId($id, false); // Prevent recursion
        }

        $data = NULL;

        // Try loading object from cache
        if (Settings::isCacheEnabled()) {
            $data = $this->getObjectDataFromCache();

            // We have to have more than only ID field
            if (count($data) === 1) {
                $data = NULL;
            }
        }

        // Do we need to update translations
        $all_multi_lng_fields_in_cache = true;
        foreach ($this->translation_fields as $v) {
            if (!isset($data['translation_data'], $data['translation_data'][$v]) || !is_array($data['translation_data'][$v])) {
                $all_multi_lng_fields_in_cache = false;
                break;
            }
        }

        $need_to_cache = false;
        // Get data from DB
        if ($data === NULL || !$all_multi_lng_fields_in_cache) {
            // Load data
            $data = q_assoc_row($this->getSelectSql());

            // Later we have to cache data
            $need_to_cache = true;
        }

        // Load entity data
        if ($data) {
            $this->loadDataFromArray($data, true);
        }

        // Save to cache
        if ($need_to_cache) {
            $this->saveObjectDataToCache();
        }

        return $this;
    }

    private function getObjectDataFromCache()
    {
        if (!Settings::isCacheEnabled()) {
            return NULL;
        }

        return Cacher::getInstance()->getDefaultCacher()->get($this->getCacheKey());
    }

    /**
     * @param array $data
     * @param bool $skip_changed_fields
     * @return $this
     */
    public function loadDataFromArray(array $data, $skip_changed_fields = false) {
        // Set usual properties
        foreach ($data as $k => $v) {
            $this->setField($k, $v, $skip_changed_fields);
        }

        // Load Multi lng data
        foreach ($this->translation_fields as $v) {
            if (isset($data['translation_data'], $data['translation_data'][$v]) && is_array($data['translation_data'][$v])) {
                $this->translation_data[$v] = $data['translation_data'][$v];
            } elseif (isset($data[$v]) && is_array($data[$v])) {
                $this->translation_data[$v] = $data[$v];
            } elseif (isset($this->data[$v])) {
                $this->translation_data[$v] = Translations::get($this->data[$v]);
            }
        }

        if ($this->encrypted_fields) {
            $this->decryptValues();
        }

        $this->afterLoad();

        return $this;
    }

    /**
     * Set data of current object in cache
     */
    private function saveObjectDataToCache()
    {
        Cacher::getInstance()->getDefaultCacher()->set($this->getCacheKey(), $this->getAsArray(), 3600);

        return $this;
    }

    /**
     * @param bool $load_translations
     * @return array
     */
    public function getAsArray($load_translations = false) {
        $res = $this->data;

        // Multi lng data in separate field
        foreach ($this->translation_fields as $v) {
            $tmp = [];
            if (isset($this->translation_data[$v])) {
                $tmp = $this->translation_data[$v];
            }
            $res['translation_data'][$v] = $tmp;
        }

        if ($load_translations) {
            foreach ($res['translation_data'] as $translation_field => $translation_field_data) {
                $res[$translation_field] = $translation_field_data[LNG];
            }
            unset($res['translation_data']);
        }

        return $res;
    }

    /**
     * @return $this
     */
    private function create()
    {
        $this->beforeCreate();

        $data = [];

        // Get all fields in DB table
        $fields = SQL::getFields($this->getDbTableName());

        // Clear ID if created from another data
        unset($this->data['id']);

        // Set data values for every available field
        foreach ($fields as $v) {
            // Translation
            if (in_array($v, $this->translation_fields) && isset($this->translation_data[$v]) && is_array($this->translation_data[$v])) {
                if (isset($this->translation_data[$v]['id']) ) {
                    unset($this->translation_data[$v]['id']); // Save new Translation
                }
                $data[$v] = Translations::save($this->translation_data[$v]);

                $this->setField($v, $data[$v]);
            } else {
                // Usual field
                if ($this->getField($v) !== NULL) {
                    $data[$v] = $this->getField($v);
                }
            }
        }

        // Create entry in database
        $this->data['id'] = SQL::add($this->getDbTableName(), $data, true, $this->update_on_duplicate, $this->insert_low_priority, $this->insert_delayed);

        $this->afterCreate();

        return $this;
    }

    public function isFieldChangedForUpdate($field) {
        return isset($this->changed_fields_for_update[$field]);
    }

    public function getChangedDataFields() {
        return array_keys($this->changed_fields_for_update);
    }

    /**
     * Method for catching setField + getField
     * @param $name
     * @param $args
     * @return string
     */
    public function __call($name, $args) {
        $prefix = substr($name, 0, 3);

        if ($prefix == 'get' || $prefix == 'set') {
            $method_to_call = $prefix . 'Field';
            $param = substr($name, 3); // Cut "set" or "get"
            $param = Converter::from_camel_case($param);
            $param = strtolower($param);

            return $this->{$method_to_call}(strtolower($param), ($args ? $args[0] : ''));
        } else {
            dump('Method "'. $name .'" unknown');
        }
    }

    public function enableUpdateOnDuplicate() {
        $this->update_on_duplicate = true;

        return $this;
    }

    public function enableSpecialCharEncodingForHtml() {
        $this->encode_special_chars_for_html = true;

        return $this;
    }

    public function addCustomCallbackForFields(callable $callback) {
        $this->field_callbacks[] = $callback;

        return $this;
    }

    public function enableSavingWithLowPriority()
    {
        $this->insert_low_priority = true;
    }

    public function enableSavingDelayedWithNoReturn()
    {
        $this->insert_delayed = true;
    }

    public function getSelectSql()
    {
        return 'SELECT * FROM `'. $this->getDbTableName() .'` WHERE `id` = "'. $this->getId() .'"';
    }

    /**
     * @param mixed $data
     */
    protected function debug($data)
    {
        if (!$this->debug) return;

        dump($data);
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

    public function setDbTableName($db_table) {
        $this->db_table = $db_table;

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

    public function getTranslationFields() {
        return $this->translation_fields;
    }

    protected function encryptValues() {
        $key = $this->getEncryptionCheckSumKey();

        foreach ($this->encrypted_fields as $field_name) {
            if (isset($this->data[$field_name])) {
                if (is_string($this->data[$field_name]) && !self::isFieldEncrypted($this->data[$field_name])) {
                    $this->data[$field_name] = SimpleCrypto::encrypt($this->data[$field_name], $key);
                }
            }

            if (isset($this->translation_data[$field_name], $this->translation_data[$field_name][LNG])) {
                if (is_string($this->translation_data[$field_name][LNG]) && !self::isFieldEncrypted($this->translation_data[$field_name][LNG])) {
                    $this->translation_data[$field_name][LNG] = SimpleCrypto::encrypt($this->translation_data[$field_name][LNG], $key);
                }
            }
        }
    }

    public function addFieldForDecryption($field_name) {
        $this->encrypted_fields[] = $field_name;

        $key = $this->getEncryptionCheckSumKey();

        // Decrypt field
        if (isset($this->data[$field_name])) {
            if (is_string($this->data[$field_name]) && self::isFieldEncrypted($this->data[$field_name])) {
                $this->data[$field_name] = SimpleCrypto::decrypt($this->data[$field_name], $key);
            }
        }

        if (isset($this->translation_data[$field_name], $this->translation_data[$field_name][LNG])) {
            if (is_string($this->translation_data[$field_name][LNG]) && self::isFieldEncrypted($this->translation_data[$field_name][LNG])) {
                $this->translation_data[$field_name][LNG] = SimpleCrypto::decrypt($this->translation_data[$field_name][LNG], $key);
            }
        }

        return $this;
    }

    protected function decryptValues() {
        $key = $this->getEncryptionCheckSumKey();

        foreach ($this->encrypted_fields as $field_name) {
            if (isset($this->data[$field_name])) {
                if (is_string($this->data[$field_name]) && self::isFieldEncrypted($this->data[$field_name])) {
                    $this->data[$field_name] = SimpleCrypto::decrypt($this->data[$field_name], $key);
                }
            }

            if (isset($this->translation_data[$field_name], $this->translation_data[$field_name][LNG])) {
                if (is_string($this->translation_data[$field_name][LNG]) && self::isFieldEncrypted($this->translation_data[$field_name][LNG])) {
                    $this->translation_data[$field_name][LNG] = SimpleCrypto::decrypt($this->translation_data[$field_name][LNG], $key);
                }
            }
        }
    }

    public static function getEncryptionCheckSumKey() {
        if (self::$encryption_key) {
            $config = Configuration::getInstance();
            self::$encryption_key = crc32(
                // All sensitive data
                $config->get('cms')['unique_key']
                . CMS_NAME
                . CMS_DEVELOPERS
                . CMS_OWNER_COMPANY
                . CMS_SUPPORT_EMAIL
                . CMS_SITE
            );
        }

        return self::$encryption_key;
    }

    public static function isFieldEncrypted($text) {
        $key = SimpleCrypto::PREFIX;
        return substr($text, 0, strlen($key)) == SimpleCrypto::PREFIX;
    }


    /**
     * Auto-call before object is Deleted
     */
    protected function beforeDelete()
    {

    }


    /**
     * Auto-call after object is Deleted
     */
    protected function afterDelete()
    {

    }


    /**
     * Auto-call before any Create or Update
     */
    protected function beforeSave() {

    }


    /**
     * Auto-call after any Create or Update
     */
    protected function afterSave() {

    }


    /**
     * Auto-call before any Update
     */
    protected function beforeUpdate() {

    }


    /**
     * Auto-call after any Update
     */
    protected function afterUpdate() {

    }


    /**
     * Auto-call before any Create
     */
    protected function beforeCreate() {

    }


    /**
     * Auto-call after any Create
     */
    protected function afterCreate() {

    }


    /**
     * Auto-call before after object is loaded with data
     */
    protected function afterLoad()
    {

    }

    public function __clone()
    {
        $this->setId(0, false);
    }
}