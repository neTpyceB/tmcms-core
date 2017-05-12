<?php

//declare(strict_types=1);

use TMCms\Routing\Entity\PagesDomainEntityRepository;
use TMCms\Routing\Interfaces\IMiddleware;

class DomainLimitationsMiddleware implements IMiddleware
{
    public function run(array $params = [])
    {
        // TODO save urls in separate table
        // TODO stet restrictions to languages and pages, etc. here in this class
        // Check if we have current domain in any limitations
//        $domains = new PagesDomainEntityRepository();
//        $domains->setWhereUrls(CFG_DOMAIN);
//        dump($domains->getSelectSql());
    }
}