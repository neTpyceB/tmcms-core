<?php

namespace TMCms\Admin\Entity;

use TMCms\Orm\EntityRepository;

class MigrationEntityRepository extends EntityRepository
{
    protected $table_structure = [
        'fields' => [
            'filename' => [
                'type' => 'varchar'
            ],
            'ts' => [
                'type' => 'int',
                'unsigned' => true,
            ],
        ]
    ];
}