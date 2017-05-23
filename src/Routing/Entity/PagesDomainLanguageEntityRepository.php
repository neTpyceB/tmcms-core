<?php

namespace TMCms\Routing\Entity;

use TMCms\Orm\EntityRepository;

/**
 * Class PagesDomainLanguageEntityRepository
 * @package TMCms\Routing\Entity
 *
 * @method $this setWhereDomainId(int $domain_id)
 * @method $this setWhereLanguageId(int $language_id)
 */
class PagesDomainLanguageEntityRepository extends EntityRepository
{
    protected $db_table = 'cms_pages_domain_languages';
    protected $table_structure = [
        'fields' => [
            'domain_id' => [
                'type' => 'index',
            ],
            'language'  => [
                'type'   => 'char',
                'length' => 2,
            ],
        ],
    ];
}