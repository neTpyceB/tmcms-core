<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Element;

use TMCms\HTML\Cms\HelperBox;
use TMCms\HTML\Element\InputText;
use TMCms\Strings\UID;

defined('INC') or exit;


class CmsInputText extends InputText
{
    protected $hint_format = '';
    protected $helper = true;
    protected $value_array = []; // Just ot be saved
    protected $uid = false;

    /**
     * @param        $name
     * @param string $value
     * @param string $id
     */
    public function __construct(string $name, string $value = '', $id = '')
    {
        parent::__construct($name, $value, $id);

        $this->setValue($value);

        $this->addCssClass('form-control');
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setValue(string $value)
    {
        if (is_array($value)) {
            $this->setValueArray($value);
            $value = implode(', ', $value);
        }

        $this->setAttribute('value', $value);

        return $this;
    }

    /**
     * @param array $value
     *
     * @return $this
     */
    public function setValueArray(array $value = [])
    {
        $this->value_array = $value;

        return $this;
    }

    /**
     * @param string $name
     * @param string $value
     * @param string $id
     *
     * @return $this
     */
    public static function getInstance($name, $value = '', $id = '')
    {
        return new self($name, $value, $id);
    }

    /**
     * @return $this
     */
    public function disableHelperBox()
    {
        $this->helper = false;

        return $this;
    }

    /**
     * @return bool
     */
    public function getHelperBox(): bool
    {
        return $this->helper;
    }

    /**
     * @return $this
     */
    public function disableBackupBlock()
    {
        return $this->setBackup(false);
    }

    /**
     * @return bool
     */
    public function getUid(): bool
    {
        return $this->uid;
    }

    /**
     * @return bool
     */
    public function isUid(): bool
    {
        return (bool)$this->uid;
    }

    /**
     * Enables UID function to change non-latin chars to latin
     *
     * @param bool|string $source_tag     if false, disables uid
     * @param int         $max_uid_length if string - contanes function for uid
     * @param bool        $connect_onload
     * @param bool        $connect_onchange
     *
     * @return $this
     */
    public function setUid(string $source_tag, int $max_uid_length = 255, bool $connect_onload = false, bool $connect_onchange = false)
    {
        if (!$source_tag) {
            $this->uid = false;

            return $this;
        }

        $this->uid = [];
        $this->uid['source'] = $source_tag;

        if (!$max_uid_length) {
            $this->uid['generate'] = false; // No uid function
        } else {
            $this->uid['generate'] = true;
            $this->uid['max_uid_length'] = $max_uid_length;
            $this->uid['connect_onload'] = $connect_onload;
            $this->uid['connect_onchange'] = $connect_onchange;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        // If calendar is attached
        if ($this->isEnabledCalendarDatepicker()) {
            $this->addCssClass('datepicker');
            $this->enableReadOnly();
        }

        // IF calendar with times is attached
        if ($this->isEnabledDateTimePicker()) {
            $this->addCssClass('datetimepicker');
        }

        $helper = $this->helper ? new HelperBox($this->getId(), $this->getMaxlength(), $this->getBackup() && !$this->isDisabled(), $this->getFormatHelpText(), $this->getValue()) : '';

        // If editable input for tables
        if ($this->isEnabledXEditable()) {
            return '<a ' . $this->getCommonElementValidationAttributes() . ' data-type="text" data-pk="' . $this->id . '" ' . $this->getAttributesString() . '>' . $this->getValue() . '</a>';
        }

        $input = '<input' . ($this->isFieldRequired() ? ' required' : '') . $this->getCommonElementValidationAttributes() . ' type="text" ' . $this->getAttributesString() . '/>';

        // Simple return
        if (!$this->isUid()) {
            return $input . $helper;
        }

        // Attach script to transcript chars
        return $this->generateUid2text() . '<table width="100%" cellpadding="0" cellspacing="0"><tr><td width="100%">' . $input
            . '</td><td valign="top"><input type="button" class="btn btn-info btn-outline" value="' . __('Refresh') . '" onclick="' . ($this->uid['function'] ? $this->uid['function'] . '()' : '') . '"/></td></td></tr></table>' . $helper;
    }

    /**
     * @return string
     */
    public function getFormatHelpText(): string
    {
        return $this->hint_format;
    }

    /**
     * @return string
     */
    public function generateUid2text(): string
    {
        $this->uid['function'] = '';

        if ($this->uid) {
            $id = $this->getId();
            $source = $this->uid['source'];

            // Cut language part
            if ($this->isTranslationEnabled()) {
                $source .= substr($id, -4);
            }

            $this->uid['function'] = 'uidFromTextEvent_' . $source . '_' . $id;

            if ($this->uid['generate']) {
                ob_start();
                UID::textToUidJs(true, [$source => $id], $this->uid['max_uid_length'], $this->uid['connect_onload'], $this->uid['connect_onchange']);

                return ob_get_clean();
            }

            if (isset($this->uid['script'])) {
                return '<script>' . $this->uid['script'] . '</script>';
            }
        }

        return '';
    }

    /**
     * @param string $format
     *
     * @return $this
     */
    public function setFormatHelpText(string $format)
    {
        $this->enableHelperBox();
        $this->hint_format = $format;

        return $this;
    }

    /**
     * @return $this
     */
    public function enableHelperBox()
    {
        $this->helper = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function value_array()
    {
        return $this->value_array;
    }
}