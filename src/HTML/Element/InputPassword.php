<?php
declare(strict_types=1);

namespace TMCms\HTML\Element;

\defined('INC') or exit;

/**
 * Class InputPassword
 * @package TMCms\HTML\Element
 */
class InputPassword extends InputText
{
    /**
     * @param string $name
     * @param string $value
     * @param string $id
     */
    public function __construct(string $name, string $value = '', string $id = '')
    {
        parent::__construct($name, $value, $id);

        $this->setType('password');
    }
}
