<?php

namespace neTpyceB\TMCms\Log\Entity;

use neTpyceB\TMCms\Orm\EntityRepository;

/**
 * Class AdminUsageEntityRepository
 * @package neTpyceB\TMCms\Log\Entity
 */
class AdminUsageEntityRepository extends EntityRepository
{
    protected $db_table = 'cms_usage';
}