<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms;

use function implode;

defined('INC') or exit;

/**
 * Class Helper
 */
class HelperBox
{
    private $element_id;
    private $maxlength;
    private $backup;
    private $hintFormat;
    private $value;

    /**
     * @param string $element_id
     * @param string $maxlength
     * @param bool   $enable_backup
     * @param string $hintFormat
     * @param string $value
     */
    public function __construct(string $element_id, string $maxlength, bool $enable_backup, string $hintFormat, string $value)
    {
        $this->element_id = $element_id;
        $this->maxlength = $maxlength;
        $this->backup = $enable_backup;
        $this->hintFormat = $hintFormat;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $res = [];

        if ($this->backup) {
            $res[] =
                '<span id="' . $this->element_id . '_restore" style="display: none;">Unsaved version is available.
                    <a href="" onclick="HTMLGen.storage.restore(\'' . $this->element_id . '\');return false;">Restore.</a>
                    <a href="" onclick="HTMLGen.storage.hide(\'' . $this->element_id . '\');return false;">Hide.</a>
                </span>';
        }

        if ($this->hintFormat) {
            $res[] = '<span>Format: <b>' . $this->hintFormat . '</b></span>';
        }

        if ($this->maxlength) {
            $res[] = '<span>Limit: <b>' . $this->maxlength . '</b></span>';
        }

        $res[] = '<span>Symbols: <b id="helperbox_element_' . $this->element_id . '">0</b></span>';

        return '<script>HTMLGen.register.text(\'' . $this->element_id . '\', ' . ($this->backup ? '\'' . md5(is_scalar($this->value) ? $this->value : serialize($this->value)) . '\'' : 'false') . ', \'' . urlencode(implode('', $res)) . '\')</script>';
    }
}