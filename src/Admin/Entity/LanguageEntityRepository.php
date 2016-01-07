<?php
namespace TMCms\Admin\Entity;

use TMCms\Orm\EntityRepository;

/**
 * Class LanguageEntityRepository
 * @package TMCms\Admin\Entity
 *
 * @method setWhereShort(string $short)
 */
class LanguageEntityRepository extends EntityRepository {
    protected $db_table = 'cms_languages';
    protected $table_structure = [
        'fields' => [
            'short' => [
                'type' => 'char',
                'length' => 2,
            ],
            'full' => [
                'type' => 'varchar',
                'length' => 32,
            ],
        ],
    ];
}