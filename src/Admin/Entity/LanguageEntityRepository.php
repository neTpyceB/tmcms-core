<?php
declare(strict_types=1);

namespace TMCms\Admin\Entity;

use TMCms\Orm\EntityRepository;

/**
 * Class LanguageEntityRepository
 * @package TMCms\Admin\Entity
 *
 * @method setWhereFull(string $full)
 * @method setWhereShort(string $short)
 */
class LanguageEntityRepository extends EntityRepository
{
    protected $db_table = 'cms_languages';
    protected $table_structure = [
        'fields' => [
            'short' => [
                'type'   => 'char',
                'length' => 2,
            ],
            'full'  => [
                'type'   => 'varchar',
            ],
        ],
    ];

    const ADMIN_LANGUAGE_DEFAULT_SHORT = 'en';
    const ADMIN_LANGUAGE_DEFAULT_FULL = 'English';
}