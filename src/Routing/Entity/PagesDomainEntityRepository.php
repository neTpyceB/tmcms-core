<?php

namespace TMCms\Routing\Entity;

use TMCms\Orm\EntityRepository;

class PagesDomainEntityRepository extends EntityRepository
{
    protected $db_table = 'cms_pages_domains';
    protected $table_structure = [
        'fields' => [
            'name'      => [
                'type' => 'varchar',
            ],
        ],
    ];
}