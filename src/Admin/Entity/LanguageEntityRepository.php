<?php
declare(strict_types=1);

namespace TMCms\Admin\Entity;

use TMCms\Orm\EntityRepository;
use TMCms\Orm\TableStructure;

/**
 * Class LanguageEntityRepository
 * @package TMCms\Admin\Entity
 *
 * @method setWhereFull(string $full)
 * @method setWhereShort(string $short)
 */
class LanguageEntityRepository extends EntityRepository
{
    const FIELD_FULL = 'full';
    const FIELD_SHORT = 'short';

    const TABLE_NAME = 'cms_languages';

    protected $db_table = self::TABLE_NAME;
    protected $table_structure = [
        'fields' => [
            self::FIELD_SHORT => [
                'type'   => TableStructure::FIELD_TYPE_CHAR,
                'length' => 2
            ],
            self::FIELD_FULL  => [
                'type'   => TableStructure::FIELD_TYPE_VARCHAR_255
            ]
        ]
    ];
}
