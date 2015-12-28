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

    protected $table_structure = [
        'fields' => [
            'ts' => [
                'type' => 'int',
                'unsigned' => true,
            ],
            'user_id' => [
                'type' => 'int',
                'unsigned' => true,
            ],
            'url' => [
                'type' => 'varchar'
            ],
            'msg' => [
                'type' => 'text'
            ],
            'p' => [
                'type' => 'varchar',
                'length' => 50,
            ],
            'do' => [
                'type' => 'varchar',
                'length' => 50,
            ],
        ],
        'indexes' => [
            'user_id' => [
                'type' => 'key'
            ]
        ],
    ];

    public function setWhereOld($last_ts)
    {
        $this->addWhereFieldIsLower('ts', $last_ts);

        return $this;
    }
}