<?php
declare(strict_types=1);

namespace TMCms\HTML\Element;

use TMCms\HTML\Cms\Element\CmsInputTextRandom;
use TMCms\HTML\Element;

\defined('INC') or exit;

/**
 * Class InputText
 * @package TMCms\HTML\Element
 */
class InputText extends Element
{
    private $plugin_xeditable = false;

    protected $js_function_for_random = '';

    /**
     * @param string $name
     * @param string $value
     * @param string $id
     */
    public function __construct(string $name, string $value = '', string $id = '')
    {
        parent::__construct();

        $this->setType('text');
        $this->setName($name);
        $this->setValue($value);
        $this->setId($id ?: $name);
    }

    /**
     * @param string $name
     * @param string $value
     * @param string $id
     *
     * @return $this
     */
    public static function getInstance(string $name, string$value = '', string $id = '')
    {
        return new self($name, $value, $id);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return '<input ' . $this->getAttributesString() . '>';
    }

    /**
     * @return $this
     */
    public function enableXEditable() {
        $this->plugin_xeditable = true;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabledXEditable(): bool
    {
        return $this->plugin_xeditable;
    }

    /**
     * @param bool $letters
     * @param bool $digits
     * @param int $length
     * @param bool $capitalization
     * @param bool $alternation
     * @param string $symbol_string
     *
     * @return $this
     */
    public function setRandomGenerator(bool $letters = true, bool $digits = false, int $length = 16, bool $capitalization = false, bool $alternation = true, string $symbol_string = '')
    {
        if (abs($length) < 6) {
            $length = 16;
        }

        ob_start();
        ?>
        <script>
            function random_for_input() {
                var res = '', i = 0;
                <?php if ($alternation): ?>
                var v = 'eyuioa', c = 'qwrtpsdfghjklzxcvbnm', vl = v.length - 1, cl = c.length - 1, change = false;

                for (; i < <?=$length?>; i++) {
                    res += change ? v.charAt(rand(0, vl)) : c.charAt(rand(0, cl));
                    change = !change;
                }
                <?php else: ?>
                var s = '<?= $symbol_string ? $symbol_string : (($letters ? 'qertyupasdfkzxcvbnm' : '') . ($capitalization ? 'QERTYUPASDFKZXCVBNM' : '') . ($digits ? '123456789' : '')) ?>';

                var sl = s.length - 1;

                for (; i < <?=$length?>; i++) {
                    res += s.charAt(rand(0, sl));
                }
                <?php endif; ?>
                return res;
            }
        </script>
        <?php

        $this->js_function_for_random = ob_get_clean();

        return $this;
    }
}
