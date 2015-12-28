<?php

namespace neTpyceB\TMCms\Log\Entity;

use neTpyceB\TMCms\Orm\EntityRepository;

/**
 * Class ErrorLogEntityRepository
 * @package neTpyceB\TMCms\Log\Entity
 */
class ErrorLogEntityRepository extends EntityRepository
{
    protected $db_table = 'cms_error_log';

    protected $table_structure = [
        'fields' => [
            'ts' => [
                'type' => 'int',
                'unsigned' => true,
            ],
            'ip_long' => [
                'type' => 'int',
                'unsigned' => true,
            ],
            'agent' => [
                'type' => 'varchar'
            ],
            'type' => [
                'type' => 'varchar',
                'length' => 10,
            ],
            'msg' => [
                'type' => 'text',
            ],
            'file' => [
                'type' => 'varchar',
            ],
            'line' => [
                'type' => 'int',
                'unsigned' => true,
            ],
            'vars' => [
                'type' => 'text',
                'comment' => 'serialized',
            ],
        ],
    ];
}