<?php

namespace neTpyceB\TMCms\Config\Entity;

use neTpyceB\TMCms\Modules\Entity;

/**
 * Class Setting
 * @package neTpyceB\TMCms\Setting\Object
 *
 * @method string getName()
 * @method string getValue()
 *
 * @method setName(string $name)
 * @method setValue(string $value)
 */
class Setting extends Entity
{
    protected $db_table = 'cms_settings';
}