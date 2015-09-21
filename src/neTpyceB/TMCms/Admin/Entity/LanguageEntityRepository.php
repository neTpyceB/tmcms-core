<?php
namespace neTpyceB\TMCms\Admin\Entity;

use neTpyceB\TMCms\Modules\EntityRepository;

/**
 * Class LanguageEntityRepository
 * @package neTpyceB\TMCms\Admin\Entity
 *
 * @method setWhereShort(string $short)
 */
class LanguageEntityRepository extends EntityRepository {
    protected $db_table = 'cms_languages';
}