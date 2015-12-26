<?php

namespace neTpyceB\TMCms\Log\Entity;

use neTpyceB\TMCms\Orm\EntityRepository;

/**
 * Class UsageEntityRepository
 * @package neTpyceB\TMCms\Log\Entity
 *
 * @method $this setWhereUserId(int $user_id)
 */
class AppLogEntityRepository extends EntityRepository
{
    protected $db_table = 'cms_app_log';

    public function setWhereOld($last_ts)
    {
        $this->addWhereFieldIsLower('ts', $last_ts);

        return $this;
    }
}