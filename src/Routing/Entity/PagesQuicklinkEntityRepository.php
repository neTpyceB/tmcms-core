<?php

namespace TMCms\Routing\Entity;

use TMCms\Orm\EntityRepository;

class PagesQuicklinkEntityRepository extends EntityRepository
{
    public function setWhereAnyName($value)
    {
        $this->addWhereFieldAsString(' OR `name` = "' . $value . '" ');
    }
}