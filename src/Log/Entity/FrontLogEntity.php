<?php

namespace TMCms\Log\Entity;

use TMCms\Orm\Entity;

/**
 * Class FrontLogEntity
 * @package TMCms\Log\Entity
 */
class FrontLogEntity extends Entity
{
    protected $db_table = 'cms_front_log';

    protected function beforeCreate()
    {
        $this->setTs(NOW);
        $this->setIpLong(IP_LONG);
        $this->setVisitorHash(VISITOR_HASH);
    }
}