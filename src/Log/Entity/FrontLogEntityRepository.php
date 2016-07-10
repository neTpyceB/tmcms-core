<?php

namespace TMCms\Log\Entity;

use TMCms\Orm\EntityRepository;

/**
 * Class FrontLogEntityRepository
 * @package TMCms\Log\Entity
 */
class FrontLogEntityRepository extends EntityRepository
{
    protected $db_table = 'cms_front_log';

    protected $table_structure = [
        'fields' => [
            'ts' => [
                'type' => 'ts',
            ],
            'ip' => [
                'type' => 'varchar',
                'length' => 15,
            ],
            'flag' => [
                'type' => 'varchar',
            ],
            'visitor_hash' => [
                'type' => 'char',
                'length' => 32,
            ],
            'text' => [
                'type' => 'text'
            ],
        ],
    ];
}