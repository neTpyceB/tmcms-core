<?php
declare(strict_types=1);

namespace TMCms\Services\Entity;

use TMCms\Orm\Entity;

/**
 * Class Service
 * @package TMCms\Modules\Entity
 *
 * @method string getFile()
 * @method int getLastTs()
 * @method string getTitle()
 * @method int getPeriod()
 * @method int getRunning()
 *
 * @method $this setLastTs(int $ts)
 * @method $this setRunning(int $flag)
 */
class ServiceEntity extends Entity
{
    protected $db_table = 'cms_services';
}