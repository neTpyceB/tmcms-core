<?php
declare(strict_types=1);

namespace TMCms\Orm;

use TMCms\Traits\singletonInstanceTrait;

/**
 * Class EntityDebugger
 * @package TMCms\Orm
 */
class EntityDebugger
{
    use singletonInstanceTrait;

    public function debug(AbstractEntity $entity) {
        $entity->enableDebug();
    }
}
