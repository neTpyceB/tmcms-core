<?php
declare(strict_types=1);

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
    protected $db_table = 'cms_migrations';

    protected function beforeCreate()
    {
        $this->setTs(NOW);
    }
}