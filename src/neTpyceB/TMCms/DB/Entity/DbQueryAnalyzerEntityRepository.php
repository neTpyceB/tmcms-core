<?php

namespace neTpyceB\TMCms\DB\Entity;

use neTpyceB\TMCms\Orm\EntityRepository;

class DbQueryAnalyzerEntityRepository extends EntityRepository
{
    protected $db_table = 'cms_db_queries_analyzer';

    protected $table_structure = [
        'fields' => [
            'hash' => [
                'type' => 'char',
                'length' => 32,
            ],
            'query' => [
                'type' => 'text'
            ],
            'tt' => [
                'type' => 'float',
                'length' => 6.3,
                'unsigned' => true,
            ],
            'path' => [
                'type' => 'varchar',
            ],
        ],
        'indexes' => [
            'hash' => [
                'type' => 'key'
            ]
        ],
    ];
}