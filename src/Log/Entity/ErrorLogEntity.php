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
 *
 * @method $this setAgent(string $browser_agent)
 * @method $this setIpLong(string $ip_long)
 * @method $this setTs(int $ts)
 */
class ErrorLogEntity extends Entity
{
    protected $db_table = 'cms_error_log';

    protected function beforeCreate()
    {
        parent::beforeCreate();

        $this->setAgent(USER_AGENT);
        $this->setIpLong(IP_LONG);
        $this->setTs(NOW);

        return $this;
    }
}