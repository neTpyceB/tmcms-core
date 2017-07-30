<?php
declare(strict_types=1);

namespace TMCms\Log\Entity;

use TMCms\Orm\Entity;

/**
 * Class ErrorLogEntity
 * @package TMCms\Log\Entity
 *
 * @method int getTs()
 * @method string getIpLong()
 */
class ErrorLogEntity extends Entity
{
    protected $db_table = 'cms_error_log';
}