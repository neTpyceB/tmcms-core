<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Element;

use TMCms\HTML\Element\InputText;

\defined('INC') or exit;

/**
 * Class CmsInputTextRandom
 * @package TMCms\HTML\Cms\Element
 */
class CmsInputTextRandom extends InputText {
    protected $js_function_for_random = '';

    /**
     * @param string $name
     * @param string $value
     * @param string $id
     */
    public function  __construct(string $name, string $value = '', string $id = '') {
        parent::__construct($name, $value, $id);

        $this->addCssClass('form-control');
        $this->setRandomGenerator();
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

    /**
     * @param string $name
     * @param string $value
     * @param string $id
     *
     * @return $this
     */
    public static function getInstance(string $name, string $value = '', string $id = '')
    {
        return new self($name, $value, $id);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $helper = $this->getHelperbox();

        return $this->js_function_for_random . '<table width="100%" cellpadding="0" cellspacing="0"><tr><td width="100%"><input ' . $this->getCommonElementValidationAttributes() . $this->getAttributesString() . '></td><td valign="top"><input type="button" class="btn btn-info btn-outline" value="' . __('Random') . '" onclick="document.getElementById(\'' . $this->getId() . '\').value=random_for_input()"></td></td></tr></table>' . $helper;
    }
}
