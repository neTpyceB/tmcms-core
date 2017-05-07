<?php

namespace TMCms\Routing\Entity;

use TMCms\Orm\Entity;

/**
 * Class PageComponentsCachedEntity
 * @package TMCms\Routing\Entity
 *
 * @method $this setClass(string $class)
 * @method $this setPageId(int $page_id)
 */
class PageComponentsCachedEntity extends Entity
{
    protected $db_table = 'cms_pages_components_cached';
}