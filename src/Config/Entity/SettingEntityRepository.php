<?php

namespace TMCms\Config\Entity;

use TMCms\Orm\EntityRepository;

/**
 * Class SettingCollection
 * @package TMCms\Config\Object
 *
 * @method setWhereName(string $name)
 * @method setWhereValue(string $value)
 */
class SettingEntityRepository extends EntityRepository
{
    protected $table_structure = [
        'fields' => [
            'name' => [
                'type' => 'varchar',
            ],
            'value' => [
                'type' => 'text',
            ],
        ],
    ];

    public function setWherePrefix($prefix)
    {
        $this->addWhereFieldIsLike('name', $prefix, true, false);
    }

    public function setSkipModules()
    {
        $this->addWhereFieldIsNotLike('name', 'm_', false);
    }
}