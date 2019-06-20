<?php
declare(strict_types=1);

namespace TMCms\Admin\Entity;

use TMCms\Orm\EntityRepository;
use TMCms\Orm\TableStructure;

/**
 * @method setWhereIpLong(string $ip_long)
 * @method setWhereSid(string $sid)
 * @method setWhereTs(int $ts)
 * @method setWhereUserId(int $user_id)
 */
class UsersSessionEntityRepository extends EntityRepository
{
    protected $db_table = 'cms_users_sessions';

    protected $table_structure = [
        'fields' => [
            'user_id' => [
                'type' => TableStructure::FIELD_TYPE_INDEX,
            ],
            'sid'     => [
                'type'   => TableStructure::FIELD_TYPE_CHAR,
                'length' => 32,
            ],
            'ip_long' => [
                'type'     => TableStructure::FIELD_TYPE_UNSIGNED_INTEGER,
            ],
            'ts'      => [
                'type'     => TableStructure::FIELD_TYPE_UNSIGNED_INTEGER,
            ],
        ],
    ];

    public function setOnlyOutdated()
    {
        $this->addWhereFieldIsLower('ts', NOW - 86400);

        return $this;
    }
}
