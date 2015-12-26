<?php

namespace neTpyceB\TMCms\Routing\Entity;

use neTpyceB\TMCms\Orm\Entity;

/**
 * Class WordEntity
 * @package neTpyceB\TMCms\Routing\Entity
 *
 * @method string getWord()
 * @method setName(string $name)
 * @method setWord(string $word)
 */
class WordEntity extends Entity
{
    protected $db_table = 'cms_pages_words';

}