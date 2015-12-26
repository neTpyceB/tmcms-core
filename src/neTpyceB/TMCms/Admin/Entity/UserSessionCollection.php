<?php

namespace neTpyceB\TMCms\Admin\Entity;

use neTpyceB\TMCms\Orm\EntityRepository;

/**
 * @method setWhereSid(string $sid)
 * @method setWhereUserId(int $user_id)
 */
class UserSessionCollection extends EntityRepository {
    public function setOnlyOutdated()
    {
        $this->addWhereFieldIsLower('ts', NOW - 86400);

        return $this;
    }
}