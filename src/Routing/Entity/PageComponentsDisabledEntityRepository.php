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
}