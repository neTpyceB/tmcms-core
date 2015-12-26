<?php

namespace neTpyceB\TMCms\Routing\Entity;

use neTpyceB\TMCms\Orm\EntityRepository;

class PagesQuicklinkEntityRepository extends EntityRepository
{
    public function setWhereAnyName($value)
    {
        $this->addWhereFieldAsString(' OR `name` = "' . $value . '" ');
    }
}