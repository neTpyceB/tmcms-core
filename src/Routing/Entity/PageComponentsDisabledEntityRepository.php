<?php

namespace TMCms\Routing\Entity;

use TMCms\Orm\EntityRepository;

/**
 * Class PageComponentsDisabledEntityRepository
 * @package TMCms\Admin\Structure\Entity
 *
 * @method setWherePageId(int $page_id)
 */
class PageComponentsDisabledEntityRepository extends EntityRepository
{
    protected $db_table = 'cms_pages_components_disabled';
    protected $table_structure = [
        'fields' => [
            'page_id' => [
                'type' => 'int',
                'unsigned' => true,
            ],
            'class' => [
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