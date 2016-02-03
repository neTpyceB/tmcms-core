<?php

namespace TMCms\Services\Entity;

use TMCms\Orm\EntityRepository;

class ServiceRepository extends EntityRepository
{
    protected $db_table = 'cms_services';
    protected $table_structure = [
        'fields' => [
            'title' => [
                'type' => 'varchar',
            ],
            'file' => [
                'type' => 'varchar',
            ],
            'last_ts' => [
                'type' => 'int',
                'unsigned' => true,
            ],
            'period' => [
                'type' => 'int',
                'unsigned' => true,
            ],
            'running' => [
                'type' => 'bool',
            ],
            'auto_start' => [
                'type' => 'bool',
            ],
        ],
    ];
}