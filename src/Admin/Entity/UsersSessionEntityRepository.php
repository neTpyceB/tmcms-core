<?php

namespace TMCms\Admin\Entity;

use TMCms\Orm\EntityRepository;

/**
 * @method setWhereSid(string $sid)
 * @method setWhereUserId(int $user_id)
 */
class UsersSessionEntityRepository extends EntityRepository {
    public function setOnlyOutdated()
    {
        $this->addWhereFieldIsLower('ts', NOW - 86400);

        return $this;
    }
}