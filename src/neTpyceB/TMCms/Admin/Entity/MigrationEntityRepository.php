<?php

namespace neTpyceB\TMCms\Admin\Entity;

use neTpyceB\TMCms\Orm\EntityRepository;

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