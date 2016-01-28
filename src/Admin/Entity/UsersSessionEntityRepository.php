<?php

namespace TMCms\Admin\Entity;

use TMCms\Orm\EntityRepository;

/**
 * @method setWhereSid(string $sid)
 * @method setWhereUserId(int $user_id)
 */
class UsersSessionEntityRepository extends EntityRepository {
    protected $db_table = 'cms_users_sessions';

    protected $table_structure = [
        'fields' => [
            'user_id' => [
                'type' => 'index',
            ],
            'sid' => [
                'type' => 'char',
                'length' => 32,
            ],
            'ip_long' => [
                'type' => 'int',
                'unsigned' => true,
            ],
            'ts' => [
                'type' => 'int',
                'unsigned' => true,
            ],
        ],
    ];

    public function setOnlyOutdated()
    {
        $this->addWhereFieldIsLower('ts', NOW - 86400);

        return $this;
    }
}