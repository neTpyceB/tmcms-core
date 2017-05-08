<?php

namespace TMCms\Routing\Entity;

use TMCms\Orm\EntityRepository;

/**
 * Class PagesWordEntityRepository
 * @package TMCms\Routing\Entity
 *
 * @method $this setWhereName(string $name)
 */
class PagesWordEntityRepository extends EntityRepository
{
    protected $db_table = 'cms_pages_words';
    protected $table_structure = [
        'fields' => [
            'name' => [
                'type' => 'varchar',
            ],
            'word' => [
                'type' => 'mediumtext',
            ],
        ],
    ];
}