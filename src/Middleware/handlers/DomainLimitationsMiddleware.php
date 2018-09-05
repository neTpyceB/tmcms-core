<?php
declare(strict_types=1);

use TMCms\Cache\Cacher;
use TMCms\Config\Settings;
use TMCms\Routing\Entity\PagesDomainEntityRepository;
use TMCms\Routing\Entity\PagesDomainLanguageEntityRepository;
use TMCms\Routing\Entity\PagesDomainUrlEntityRepository;
use TMCms\Routing\Interfaces\IMiddleware;
use TMCms\Routing\Languages;

class DomainLimitationsMiddleware implements IMiddleware
{
    public function run(array $params = [])
    {
        $cache_key = 'middleware_languages_for_domain_'. CFG_DOMAIN;

        $languages = NULL;
        if (Settings::isCacheEnabled()) {
            $languages = Cacher::getInstance()->getDefaultCacher()->get($cache_key);
        }

        if ($languages === NULL) {
            // Check if we have any restrictions for current domain
            $languages = new PagesDomainLanguageEntityRepository();
            $urls = new PagesDomainUrlEntityRepository();
            $domain = new PagesDomainEntityRepository();

            $sql = '
            SELECT
                `l`.`language`
            FROM `' . $languages->getDbTableName() . '` AS `l`
            JOIN `' . $domain->getDbTableName() . '` AS `d` ON (`l`.`domain_id` = `d`.`id`)
            JOIN `' . $urls->getDbTableName() . '` AS `u` ON (`u`.`domain_id` = `d`.`id`)
            WHERE `u`.`url` = "' . sql_prepare(CFG_DOMAIN) . '"
        ';

            $languages = q_pairs($sql);

            // Save in cache
            if (Settings::isCacheEnabled()) {
                Cacher::getInstance()->getDefaultCacher()->set($cache_key, $languages);
            }
        }

        if (!$languages) {
            return;
        }

        Languages::$__domain_restricted_languages = $languages;
    }
}
