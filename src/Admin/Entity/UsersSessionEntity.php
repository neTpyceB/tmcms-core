<?php
declare(strict_types=1);

namespace TMCms\Admin\Entity;

use TMCms\Orm\Entity;

/**
 * @method $this setIpLong(string $ip_long)
 * @method $this setSid(string $sid)
 * @method $this setTs(int $ts)
 * @method $this setUserId(int $user_id)
 */
class UsersSessionEntity extends Entity
{
    protected $db_table = 'cms_users_sessions';

    protected function beforeCreate()
    {
        $this->setIpLong(IP_LONG);
        $this->setTs(NOW);
    }
}