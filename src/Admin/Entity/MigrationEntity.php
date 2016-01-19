<?php

namespace TMCms\Admin\Entity;

use TMCms\Orm\Entity;

/**
 * Class Migration
 * @package TMCms\Admin\Entity
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