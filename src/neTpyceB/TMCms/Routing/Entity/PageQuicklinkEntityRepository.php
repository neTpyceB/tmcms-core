<?php

namespace neTpyceB\TMCms\Routing\Entity;

use neTpyceB\TMCms\Orm\EntityRepository;

class PageQuicklinkEntityRepository extends EntityRepository
{
    protected $db_table = 'cms_pages_quicklinks';

    public function setWhereAnyName($value)
    {
        $this->addWhereFieldAsString(' OR `name` = "' . $value . '" ');
    }

}