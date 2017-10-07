<?php
declare(strict_types=1);

namespace TMCms\Admin\Entity;

use TMCms\Orm\EntityRepository;

class MigrationEntityRepository extends EntityRepository
{
    protected $db_table = 'cms_migrations';
    protected $table_structure = [
        'fields' => [
            'filename' => [
                'type' => 'varchar',
            ],
            'ts'       => [
                'type'     => 'ts',
            ],
        ],
    ];
}