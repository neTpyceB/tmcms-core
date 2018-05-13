<?php
declare(strict_types=1);

namespace TMCms\DB\Entity;

use TMCms\Orm\Entity;

/**
 * Class MySqlTableColumnEntity
 * @package TMCms\DB\Entity
 */
class MySqlTableColumnEntity extends Entity
{
    const COLUMN_TYPE_INT = 'int';
    const COLUMN_TYPE_VARCHAR = 'varchar';

    const COLUMN_TYPES = [
        self::COLUMN_TYPE_INT,
        self::COLUMN_TYPE_VARCHAR,
    ];

    private $name = '';
    private $type = '';
    private $length = 0;
    /** @var mixed */
    private $default_value;
    private $unsigned = false;

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string $type
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setType(string $type): self
    {
        if (!\in_array($type, self::COLUMN_TYPES, true)) {
            throw new \InvalidArgumentException('Not supported column type '. $type);
        }

        $this->type = $type;

        return $this;
    }

    /**
     * @param bool $flag
     *
     * @return $this
     */
    public function setUnsigned(bool $flag): self
    {
        $this->unsigned = $flag;

        return $this;
    }

    /**
     * @param int $length
     *
     * @return $this
     */
    public function setLength(int $length): self
    {
        $this->length = $length;

        return $this;
    }

    /**
     * @param mixed $default_value
     *
     * @return $this
     */
    public function setDefaultValue($default_value): self
    {
        $this->default_value = $default_value;

        return $this;
    }

    /**
     * @return string
     */
    public function getCreateStatement() {
        if (!$this->name) {
            throw new \InvalidArgumentException('Name is not set');
        }
        if (!$this->type) {
            throw new \InvalidArgumentException('Type is not set');
        }

        $sql = 'ALTER TABLE `'. $this->getDbTableName() .'` ADD `'. $this->name .'` '. \strtoupper($this->type);

        if ($this->length) {
            $sql .= ' ('. $this->length .') ';
        }

        if ($this->unsigned) {
            $sql .= ' UNSIGNED ';
        }

        // Can be NULL
        $sql .= ' NULL ';

        if ($this->default_value) {
            $sql .= " DEFAULT '". $this->default_value ."' ";
        }

        return $sql;
    }
}
