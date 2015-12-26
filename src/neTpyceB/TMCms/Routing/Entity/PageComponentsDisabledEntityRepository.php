<?php

namespace neTpyceB\TMCms\Routing\Entity;

use neTpyceB\TMCms\Orm\EntityRepository;

/**
 * Class PageComponentsDisabledEntityRepository
 * @package neTpyceB\TMCms\Admin\Structure\Entity
 *
 * @method setWherePageId(int $page_id)
 */
class PageComponentsDisabledEntityRepository extends EntityRepository
{
    protected $db_table = 'cms_pages_components_disabled';
}