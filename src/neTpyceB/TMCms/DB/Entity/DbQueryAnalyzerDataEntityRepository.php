<?php

namespace neTpyceB\TMCms\DB\Entity;

use neTpyceB\TMCms\Orm\EntityRepository;

class DbQueryAnalyzerDataEntityRepository extends EntityRepository
{
    protected $db_table = 'cms_db_queries_analyzer_data';

    protected $table_structure = [
        'fields' => [
            'hash' => [
                'type' => 'char',
                'length' => 32,
            ],
            'query' => [
                'type' => 'text'
            ],
            'min_tt' => [
                'type' => 'float',
                'length' => 6.3,
                'unsigned' => true,
            ],
            'avg_tt' => [
                'type' => 'float',
                'length' => 6.3,
                'unsigned' => true,
            ],
            'max_tt' => [
                'type' => 'float',
                'length' => 6.3,
                'unsigned' => true,
            ],
            'total' => [
                'type' => 'int',
                'unsigned' => true,
            ],
            'path' => [
                'type' => 'varchar',
            ],
            'uses_indexes' => [
                'type' => 'bool',
            ],
            'page_title' => [
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