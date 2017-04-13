<?php

namespace TMCms\Routing\Entity;

use TMCms\Orm\EntityRepository;

/**
 * Class PageComponentsCachedEntityRepository
 * @package TMCms\Admin\Structure\Entity
 *
 * @method setWherePageId(int $page_id)
 */
class PageComponentsCachedEntityRepository extends EntityRepository
{
    protected $db_table = 'cms_pages_components_cached';
    protected $table_structure = [
        'fields'  => [
            'page_id' => [
                'type'     => 'int',
                'unsigned' => true,
            ],
            'class'   => [
                'type' => 'varchar',
            ],
        ],
        'indexes' => [
            'page_id' => [
                'type' => 'key',
            ],
        ],
    ];
}