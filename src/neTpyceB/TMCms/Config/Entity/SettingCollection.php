<?php

namespace neTpyceB\TMCms\Config\Entity;

use neTpyceB\TMCms\Orm\EntityRepository;

/**
 * Class SettingCollection
 * @package neTpyceB\TMCms\Config\Object
 *
 * @method setWhereName(string $name)
 * @method setWhereValue(string $value)
 */
class SettingCollection extends EntityRepository
{
    protected $db_table = 'cms_settings';

    public function setWherePrefix($prefix)
    {
        $this->addWhereFieldIsLike('name', $prefix, true, false);
    }

    public function setSkipModules()
    {
        $this->addWhereFieldIsNotLike('name', 'm_', false);
    }
}