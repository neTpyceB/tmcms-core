<?php

namespace TMCms\Routing\Entity;

use TMCms\Orm\EntityRepository;

/**
 * Class PagesDomainUrlEntityRepository
 * @package TMCms\Routing\Entity
 *
 * @method $this setWhereDomainId(int $domain_id)
 * @method $this setWhereUrl(string $url)
 */
class PagesDomainUrlEntityRepository extends EntityRepository
{
    protected $db_table = 'cms_pages_domain_urls';
    protected $table_structure = [
        'fields' => [
            'domain_id' => [
                'type' => 'index',
            ],
            'url'       => [
                'type' => 'varchar',
            ],
        ],
    ];
}