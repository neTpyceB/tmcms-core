<?php

namespace neTpyceB\TMCms\Admin\Entity;

use neTpyceB\TMCms\Orm\Entity;

/**
 * Class Migration
 * @package neTpyceB\TMCms\Admin\Object
 *
 * @method setFilename(string $filename)
 * @method setTs(int $ts)
 */
class MigrationEntity extends Entity
{
    protected function beforeCreate()
    {
        $this->setTs(NOW);
    }
}