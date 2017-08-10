<?php

namespace TMCms\HTML;

use TMCms\Config\Settings;
use TMCms\HTML\Cms\Widget\FileManager;
use TMCms\HTML\Cms\Widget\GoogleMap;
use TMCms\HTML\Cms\Widget\Wysiwyg;
use TMCms\Strings\Converter;
use TMCms\Strings\UID;
use TMCms\Templates\PageHead;
use TMCms\Templates\PageTail;

defined('INC') or exit;

/**
 * Class Element
 * @package TMCms\HTML
 *
 * @method backup()
 */
abstract class Element
{
    protected $attributes = [];
    protected $invisible = false;
    protected $translation_enabled = false;
    protected $hint;
    protected $widget;
    protected $id;
    protected $class;
    protected $srcClass;
    protected $wysiwyg_enabled = false;
    protected $calendar_datepicker_enabled = false;
    protected $date_time_picker_enabled = false;
    protected $field_required = false;
    protected $backup = true;
    protected $provider = [];
    protected $value_array = [];
    private $validator_attributes = []; // For CSS and JS checks
    private $validator_checks = []; // For backend checks

    /**
     *
     */
    public function __construct()
    {

    }

    /**
     * @param string $hint
     * @return $this
     */
    public function setHintText($hint)
    {
        $this->hint = $hint;

        return $this;
    }

    /**
     * @return string
     */
    public function getHintText()
    {
        return $this->hint;
    }

    /**
     * @return $this
     */
    public function enableMultiple()
    {
        $this->setAttribute('multiple', 'multiple');

        return $this;
    }

    /**
     * Set element attribute that can not be set using other methods
     * @param string $k attribute name
     * @param string $v attribute value
     * @return $this
     */
    public function setAttribute($k, $v)
    {
        $this->attributes[strtolower($k)] = $v;

        return $this;
    }

    /**
     * @return $this
     */
    public function setDisabled()
    {
        $this->setAttribute('disabled', 'disabled');

        return $this;
    }

    /**
     * @return bool
     */
    public function isDisabled()
    {
        return $this->getAttribute('disabled');
    }

    /**
     * @param string $k element name
     * @return string
     */
    public function getAttribute($k)
    {
        $k = strtolower($k);

        return isset($this->attributes[$k]) ? $this->attributes[$k] : '';
    }

    /**
     * @return bool
     */
    public function getBackup()
    {
        return $this->backup;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function setBackup($text)
    {
        $this->backup = $text;

        return $this;
    }

    /**
     * @return $this
     */
    public function enableAutoSubmit()
    {
        $this->setAttribute('onchange', 'this.form.submit();');

        return $this;
    }

    /**
     * @return $this
     */
    public function enableReadOnly()
    {
        $this->setAttribute('readonly', 'readonly');

        return $this;
    }

    /**
     * @return bool
     */
    public function isTranslationEnabled()
    {
        return $this->translation_enabled;
    }

    /**
     * Set field to multi language
     * @return $this
     */
    public function enableTranslation()
    {
        $this->translation_enabled = true;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->getAttribute('value');
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->setAttribute('value', $value);

        return $this;
    }

    /**
     * @return array
     */
    public function getValueArray(): array
    {
        return $this->value_array;
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
     * @param int $maxlength
     * @return $this
     */
    public function setMaxlength($maxlength)
    {
        $this->setAttribute('maxlength', $maxlength);

        return $this;
    }

    /**
     * @return string
     */
    public function getMaxlength()
    {
        return $this->getAttribute('maxlength');
    }

    /**
     * @param string $rows
     * @return $this
     */
    public function setRowCount($rows)
    {
        $this->setAttribute('rows', $rows);

        return $this;
    }

    /**
     * @return string
     */
    public function getRowCount()
    {
        return $this->getAttribute('rows');
    }

    /**
     * @return $this
     */
    public function setTypeAsText()
    {
        $this->setType('text');

        return $this;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->setAttribute('type', $type);

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->getAttribute('type');
    }

    /**
     * @param string $script
     * @return $this
     */
    public function setOnclick($script)
    {
        $this->setAttribute('onclick', $script);

        return $this;
    }

    /**
     * @return string
     */
    public function getOnClick()
    {
        return $this->getAttribute('onclick');
    }

    /**
     * @param string $script
     * @return $this
     */
    public function setOnchange($script)
    {
        $this->setAttribute('onchange', $script);

        return $this;
    }

    /**
     * @return string
     */
    public function getOnchange()
    {
        return $this->getAttribute('onchange');
    }

    /**
     * @param string $script
     * @return $this
     */
    public function setOnkeyup($script)
    {
        $this->setAttribute('onkeyup', $script);

        return $this;
    }

    /**
     * @return string
     */
    public function getOnkeyup()
    {
        return $this->getAttribute('onkeyup');
    }

    /**
     * @param string $id
     * @return $this
     */
    public function setElementIdAttribute($id)
    {
        return $this->setId($id);
    }

    /**
     * @param string $class
     * @return $this
     */
    public function addCssClass($class)
    {
        $this->setAttribute('class', $this->getCssClass() . ' ' . $class);

        return $this;
    }

    /**
     * @return string
     */
    public function getCssClass()
    {
        return $this->getAttribute('class');
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->setAttribute('name', $name);

        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return ($this->id ? $this->id : Converter::nameToHtmlAttribute($this->getName()));
    }

    /**
     * @param string $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = Converter::nameToHtmlAttribute($id);

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getAttribute('name');
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param string $k value name
     * @return bool
     */
    public function hasAttribute($k)
    {
        return isset($this->attributes[strtolower($k)]);
    }

    /**
     * Return string consisting from all html element attributes
     * @param array $skip
     * @return string
     */
    public function getAttributesString(array $skip = [])
    {
        $skip = array_map('strtolower', $skip);

        $res = [];
        if (isset($this->id)) {
            $res[] = 'id="' . htmlspecialchars($this->getId(), ENT_QUOTES) . '"';
        }

        foreach ($this->attributes as $k => $v) {
            // Skip if needed
            if (in_array($k, $skip)) {
                continue;
            }

            switch ($k) {
                case 'disabled':
                case 'selected':
                case 'checked':

                    if (!$v) {
                        continue 2;
                    }

                    $v = $k;
                    break;

                case 'value':

                    $v = htmlspecialchars($v, ENT_QUOTES);
                    break;

            }

            // If attribute itself is array of attributes
            if (is_array($v)) {
                $v = implode('', $v);
            }

            $res[] = $k . '="' . $v . '"';
        }

        return implode(' ', $res);
    }


    /**
     * @return Widget
     */
    public function getWidget()
    {
        return $this->widget;
    }

    /**
     * Set Widget button near element field
     * @param Widget $widget
     * @return $this
     */
    public function setWidget(Widget $widget)
    {
        $this->widget = $widget;

        $this->widget->setOwner($this);

        return $this;
    }

    /**
     * @return bool
     */
    public function hasWidgets()
    {
        return isset($this->widget);
    }

    /**
     * @return bool
     */
    public function isInvisible()
    {
        return $this->invisible === true;
    }

    abstract public function __toString();

    /**
     * Enables WYSIWYG editor for input, e.g. textarea
     * @return $this
     */
    public function enableWysiwyg()
    {
        $this->setWidget(Wysiwyg::getInstance());

        $this->wysiwyg_enabled = true;

        return $this;
    }

    /**
     * Enables google map place picker
     * @return $this
     */
    public function enableGoogleMap($options = [])
    {
        $widget = GoogleMap::getInstance();

        $widget->options = $options;
        $this->setWidget($widget);

        PageHead::getInstance()
            ->addJsUrl('https://maps.googleapis.com/maps/api/js?key=' . Settings::get('google_api_key'));

        return $this;
    }

    /**
     * Enables Filemanager editor for input, e.g. filepicker
     * @param string $path
     * @return $this
     */
    public function enableFilemanager($path = DIR_PUBLIC_URL)
    {
        $this->setWidget(FileManager::getInstance()->path($path));

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabledWysiwyg()
    {
        return $this->wysiwyg_enabled;
    }

    /**
     * Enables simple datepicker for input
     * @return $this
     */
    public function enableCalendarDatepicker()
    {
        if (!$this->isEnabledCalendarDatepicker()) {
            // First time enabling
            PageTail::getInstance()
                ->addCssUrl('plugins/datepicker/datepicker.css')
                ->addJsUrl('plugins/datepicker/bootstrap-datepicker.js');

            $this->setDateFormat('yyyy-mm-dd');
        }

        $this->calendar_datepicker_enabled = true;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabledCalendarDatepicker()
    {
        return $this->calendar_datepicker_enabled;
    }

    /**
     * @param string $format
     * @return $this
     */
    public function setDateFormat($format)
    {
        $this->setAttribute('data-date-format', $format);

        return $this;
    }

    /**
     * Enable Bootstrap DateTimePicker on input
     * @param array $options
     * @return $this
     */
    public function enableDateTimePicker($options = [])
    {
        if (!$this->isEnabledDateTimePicker()) {
            PageTail::getInstance()
                ->addCssUrl('plugins/bootstrap-datetimepicker/css/bootstrap-datetimepicker.min.css')
                ->addJsUrl('plugins/moment/js/moment.min.js')
                ->addJsUrl('plugins/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js');
        }

        // Default format is "year-month-day hour-minute"
        if (!isset($options['format'])) {
            $options['format'] = 'YYYY-MM-DD HH:mm';
        }

        $options = array_merge($options, ['locale' => LNG]);

        PageTail::getInstance()
            ->addJs('$(".datetimepicker").datetimepicker(' . json_encode($options) . ');');

        $this->date_time_picker_enabled = true;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabledDateTimePicker()
    {
        return $this->date_time_picker_enabled;
    }

    /**
     * View mode for datepicker
     * @return $this
     */
    public function setViewOnlyYears()
    {
        $this->setAttribute('data-date-min-view-mode', 'years');

        return $this;
    }

    /**
     * View mode for datepicker
     * @return $this
     */
    public function setViewOnlyYearsAndMonths()
    {
        $this->setAttribute('data-date-min-view-mode', 'months');

        return $this;
    }

    /**
     * Validate that a required field has been filled with a non blank value
     * Set field as required to fill
     * @return $this
     */
    public function validateRequired()
    {
        $this->validator_attributes[] = 'data-parsley-required';

        $this->field_required = true;

        $this->validator_checks['required'] = true;

        return $this;
    }

    /**
     * Enable autogeneration of value in field from another field
     * @param string $from_field
     * @param string $to_field
     * @return $this
     */
    public function enableSlugGenerationUidFromField($from_field, $to_field)
    {
        UID::textToUidJs(true, [$to_field => $from_field], 255, 1, 1);

        return $this;
    }

    /**
     * @return bool
     */
    public function isFieldRequired()
    {
        return $this->field_required;
    }

    /**
     * Validates that a value is a valid email address.
     * @return $this
     */
    public function validateEmail()
    {
        $this->validator_attributes[] = 'data-parsley-type="email"';

        $this->validator_checks['type'] = 'email';

        return $this;
    }

    /**
     * Validates that a value is a valid number. HTML5 type="number" is binded with below integer validator.
     * @return $this
     */
    public function validateNumber()
    {
        $this->validator_attributes[] = 'data-parsley-type="number"';

        $this->validator_checks['type'] = 'number';

        return $this;
    }

    /**
     * Validates that a value is a valid integer.
     * @return $this
     */
    public function validateInteger()
    {
        $this->validator_attributes[] = 'data-parsley-type="integer"';

        $this->validator_checks['type'] = 'integer';

        return $this;
    }

    /**
     * Validates that a value is only digits.
     * @return $this
     */
    public function validateDigits()
    {
        $this->validator_attributes[] = 'data-parsley-type="digits"';

        $this->validator_checks['type'] = 'digits';

        return $this;
    }

    /**
     * Validates that a value is a valid alphanumeric string.
     * @return $this
     */
    public function validateAlphaNumeric()
    {
        $this->validator_attributes[] = 'data-parsley-type="alphanum"';

        $this->validator_checks['type'] = 'alphanum';

        return $this;
    }

    /**
     * Validates that a value is a valid url.
     * @return $this
     */
    public function validateUrl()
    {
        $this->validator_attributes[] = 'data-parsley-type="url"';

        $this->validator_checks['type'] = 'url';

        return $this;
    }

    /**
     * Validates that the length of a string is at least as long as the given limit.
     * @param int $length
     * @return $this
     */
    public function validateMinLength($length)
    {
        $this->validator_attributes[] = 'data-parsley-minlength="' . (int)$length . '"';

        $this->validator_checks['min_length'] = (int)$length;

        return $this;
    }

    /**
     * Validates that the length of a string is not larger than the given limit.
     * @param int $length
     * @return $this
     */
    public function validateMaxLength($length)
    {
        $this->validator_attributes[] = 'data-parsley-maxlength="' . (int)$length . '"';

        $this->validator_checks['max_length'] = (int)$length;

        return $this;
    }

    /**
     * Validates that a given string length is between some minimum and maximum value.
     * @param int $min_length
     * @param int $max_length
     * @return $this
     */
    public function validateLength($min_length, $max_length)
    {
        $this->validator_attributes[] = 'data-parsley-length="[' . (int)$min_length . ', ' . (int)$max_length . ']"';

        $this->validator_checks['min_length'] = (int)$min_length;
        $this->validator_checks['max_length'] = (int)$max_length;

        return $this;
    }

    /**
     * Validates that a given number is greater than or equal to some minimum number.
     * @param float $amount
     * @return $this
     */
    public function validateMin($amount)
    {
        $this->validator_attributes[] = 'data-parsley-min="' . (float)$amount . '"';

        $this->validator_checks['min'] = (float)$amount;

        return $this;
    }

    /**
     * Validates that a given number is less than or equal to some maximum number.
     * @param float $amount
     * @return $this
     */
    public function validateMax($amount)
    {
        $this->validator_attributes[] = 'data-parsley-max="' . (float)$amount . '"';

        $this->validator_checks['max'] = (float)$amount;

        return $this;
    }

    /**
     * Validates that a given number is between some minimum and maximum number.
     * @param float $min_amount
     * @param float $max_amount
     * @return $this
     */
    public function validateRange($min_amount, $max_amount)
    {
        $this->validator_attributes[] = 'data-parsley-range="[' . (float)$min_amount . ', ' . (float)$max_amount . ']"';

        $this->validator_checks['min'] = (float)$min_amount;
        $this->validator_checks['max'] = (float)$max_amount;

        return $this;
    }

    /**
     * Validates that a value matches a specific regular expression (regex).
     * @param int $pattern
     * @return $this
     */
    public function validatePattern($pattern)
    {
        $this->validator_attributes[] = 'data-parsley-pattern="' . $pattern . '"';

        $this->validator_checks['pattern'] = (int)$pattern;

        return $this;
    }

    /**
     * Validates that a certain minimum number of checkboxes in a group are checked.
     * @param int $count
     * @return $this
     */
    public function validateMinCheckboxesChecked($count)
    {
        $this->validator_attributes[] = 'data-parsley-mincheck="' . (int)$count . '"';

        $this->validator_checks['min_checkboxes_checked'] = (int)$count;

        return $this;
    }

    /**
     * Validates that a certain maximum number of checkboxes in a group are checked.
     * @param int $count
     * @return $this
     */
    public function validateMaxCheckboxesChecked($count)
    {
        $this->validator_attributes[] = 'data-parsley-maxcheck="' . (int)$count . '"';

        $this->validator_checks['max_checkboxes_checked'] = (int)$count;

        return $this;
    }

    /**
     * Validates that the number of checked checkboxes in a group is within a certain range.
     * @param int $min_count
     * @param int $max_count
     * @return $this
     */
    public function validateRangeCheckboxesChecked($min_count, $max_count)
    {
        $this->validator_attributes[] = 'data-parsley-check="[' . (int)$min_count . ', ' . (int)$max_count . ']"';

        $this->validator_checks['min_checkboxes_checked'] = (int)$min_count;
        $this->validator_checks['max_checkboxes_checked'] = (int)$max_count;

        return $this;
    }

    /**
     * Validates that the value is identical to another field's value (useful for password confirmation check).
     * @param string $another_field_id
     * @return $this
     */
    public function validateEqualToAnotherField($another_field_id)
    {
        $this->validator_attributes[] = 'data-parsley-equalto="#' . $another_field_id . '"';

        $this->validator_checks['equal_to_field'] = $another_field_id;

        return $this;
    }

    /**
     * @return string
     */
    public function getCommonElementValidationAttributes()
    {
        return ' ' . implode(' ', $this->getValidatorAttributes()) . ' ';
    }

    /**
     * @return array
     */
    public function getValidatorAttributes()
    {
        return $this->validator_attributes;
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function setValidatorAttributes($attributes)
    {
        $this->validator_attributes = $attributes;

        return $this;
    }

    /**
     * @return array
     */
    public function getValidatorBackendChecks()
    {
        return $this->validator_checks;
    }
}