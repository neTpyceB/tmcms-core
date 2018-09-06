<?php
declare(strict_types=1);

namespace TMCms\Container;

use RuntimeException;

\defined('INC') or exit;

/**
 * Class Base
 * @package TMCms\Container
 */
abstract class Base
{
    protected $initial_data;

    const FIELD_TYPE_BOOL = 1;
    const FIELD_TYPE_INT = 2;
    const FIELD_TYPE_STRING = 3;
    const FIELD_TYPE_ARRAY = 4;

    /**
     * Base constructor.
     *
     * @param array $provider_data from superglobal array
     */
    protected function __construct(array $provider_data)
    {
        $this->initial_data = $provider_data;
    }

    /**
     * @param string $field_name
     * @param int $field_type
     *
     * @return array|bool|int|string
     * @throws \RuntimeException
     */
    final public function getCleanedField(string $field_name, int $field_type)
    {
        switch ($field_type) {
            case self::FIELD_TYPE_BOOL:
                return $this->getCleanedFieldAsBool($field_name);

            case self::FIELD_TYPE_INT:
                return $this->getCleanedFieldAsInt($field_name);

            case self::FIELD_TYPE_STRING:
                return $this->getCleanedFieldAsString($field_name);

            case self::FIELD_TYPE_ARRAY:
                return $this->getCleanedFieldAsArray($field_name);

            default:
                throw new RuntimeException('Unknown field type requested');
        }
    }

    /**
     * @param string $field_name
     *
     * @return bool
     */
    public function getCleanedFieldAsBool(string $field_name): bool
    {
        if (empty($this->initial_data[$field_name])) {
            return false;
        }

        // Usually string "false" from javascript interpreted as true, but it must be false
        if ('false' === $this->initial_data[$field_name]) {
            return false;
        }

        return (bool)$this->initial_data[$field_name];
    }

    /**
     * @param string $field_name
     *
     * @return int
     */
    public function getCleanedFieldAsInt(string $field_name): int
    {
        if (empty($this->initial_data[$field_name])) {
            return 0;
        }

        if (!is_scalar($this->initial_data[$field_name])) {
            return 0;
        }

        return (int)$this->initial_data[$field_name];
    }

    /**
     * @param string $field_name
     *
     * @return string
     */
    public function getCleanedFieldAsString(string $field_name): string
    {
        if (empty($this->initial_data[$field_name])) {
            return '';
        }

        if (!is_scalar($this->initial_data[$field_name])) {
            return '';
        }

        return (string)$this->initial_data[$field_name];
    }

    /**
     * @param string $field_name
     *
     * @return array
     */
    public function getCleanedFieldAsArray(string $field_name): array
    {
        if (empty($this->initial_data[$field_name])) {
            return [];
        }

        // Usually string "false" from javascript interpreted as true, but it must be false
        if (!\is_array($this->initial_data[$field_name])) {
            return [];
        }

        return (array)$this->initial_data[$field_name];
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setValue(string $key, $value) {
        $this->initial_data[$key] = $value;

        return $this;
    }
}
