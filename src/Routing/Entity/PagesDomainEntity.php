<?php

namespace TMCms\Routing\Entity;

use TMCms\Orm\Entity;

/**
 * Class PagesDomainEntity
 * @package TMCms\Routing\Entity
 *
 * @method string getName()
 *
 * @method $this setName(string $name)
 */
class PagesDomainEntity extends Entity
{
    protected function beforeDelete()
    {
        $urls = new PagesDomainUrlEntityRepository();
        $urls->setWhereDomainId($this->getId());
        $urls->deleteObjectCollection();

        $languages = new PagesDomainLanguageEntityRepository();
        $languages->setWhereDomainId($this->getId());
        $languages->deleteObjectCollection();

        return $this;
    }
}