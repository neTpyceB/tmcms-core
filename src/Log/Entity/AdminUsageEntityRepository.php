<?php

namespace TMCms\Log\Entity;

use TMCms\Orm\EntityRepository;

/**
 * Class AdminUsageEntityRepository
 * @package TMCms\Log\Entity
 */
class AdminUsageEntityRepository extends EntityRepository
{
    protected $db_table = 'cms_usage';
}