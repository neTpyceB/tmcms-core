<?php
declare(strict_types=1);

namespace TMCms\Routing;

use TMCms\Container\Get;

\defined('INC') or exit;

/**
 * Class SharedMvcAliasMethods
 */
class SharedMvcAliasMethods
{

    /**
     * @param string $field_name
     * @param int $field_type
     *
     * @return array|bool|int|string use global Base container constants FIELD_TYPE_...
     */
    public function getGlobalParamGet(string $field_name, int $field_type) {
        try {
            $param = Get::getInstance()->getCleanedField($field_name, $field_type);
        } catch (\RuntimeException $e) {
            \trigger_error($e->getMessage());

            $param = null;
        }

        return $param;
    }

    /**
     * @param string $label
     *
     * @return string
     */
    public function getPathByLabel(string $label): string
    {
        return (string)Structure::getPathByLabel($label);
    }
}
