<?php
declare(strict_types=1);

namespace TMCms\Container;

use TMCms\Traits\singletonInstanceTrait;

\defined('INC') or exit;

/**
 * Class Get
 * @package TMCms\Container
 */
class Get extends Base
{
    use singletonInstanceTrait;

    public function __construct()
    {
        parent::__construct($_GET);
    }

    /**
     * @param string $field_name
     *
     * @return bool
     */
    public function getCleanedFieldAsBool(string $field_name): bool
    {
        // Special case, if we have key set but no value - we keep it as true, e.g. &status=active&order_desc = order_desc must be true
        if (isset($this->initial_data[$field_name]) && '' === $this->initial_data[$field_name]) {
            return $this->initial_data[$field_name] = true;
        }

        return parent::getCleanedFieldAsBool($field_name);
    }
}
