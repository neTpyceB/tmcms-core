<?php
declare(strict_types=1);

namespace TMCms\Admin\Entity;

use TMCms\Orm\Entity;

/**
 * Class LanguageEntity
 * @package TMCms\Admin\Entity
 *
 * @method string getFull();
 * @method string getShort();
 *
 * @method $this setFull(string $full_name);
 * @method $this setShort(string $short_name);
 */
class LanguageEntity extends Entity
{
    protected $db_table = LanguageEntityRepository::TABLE_NAME;
}
