<?php
namespace neTpyceB\TMCms\Admin\Entity;

use neTpyceB\TMCms\Modules\Entity;

/**
 * Class LanguageEntity
 * @package neTpyceB\TMCms\Admin\Entity
 *
 * @method setShort(string);
 * @method string getShort();
 * @method setFull(string);
 * @method string getFull();
 */
class LanguageEntity extends Entity {
    protected $db_table = 'cms_languages';
}