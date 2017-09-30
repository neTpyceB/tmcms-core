<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Column;

use RuntimeException;
use TMCms\HTML\Cms\Column;
use TMCms\HTML\Cms\Linker;
use TMCms\Orm\Entity;
use TMCms\Strings\SimpleCrypto;

defined('INC') or exit;

/**
 * Class ColumnData
 */
class ColumnData extends Column
{
    // Used when dataType is "number"
    private $number_format = [
        'decimals'      => 0,
        'dec_point'     => 0,
        'thousands_sep' => ' ',
    ];
    private $data_type = 'data';
    private $possible_types = ['email', 'truefalse', 'ts2date', 'ts2datetime', 'number', 'iplong', 'datetime2date', 'link', 'array'];

    /**
     * @param string $key
     *
     * @return ColumnData
     */
    public static function getInstance($key)
    {
        return new self($key);
    }

    /**
     * Get/Set column value data type
     *
     * @param string $type - one of email, truefalse, ts2date, ts2datetime, number, iplong, datetime2date, link, array
     *
     * @return $this
     */
    public function dataType(string $type)
    {
        if (!in_array($type, $this->possible_types)) {
            throw new RuntimeException('Wrong data type, available: ' . implode(', ', $this->possible_types));
        }

        $this->data_type = $type;


        // Pre-format
        switch ($this->data_type) {
            case 'ts2datetime':
            case 'ts2date':
                $this->setWidth('1%');
                $this->disableNewlines();
                break;

            case 'truefalse':
                $this->setWidth('1%');
                $this->disableNewlines();
                $this->enableCenterAlign();
                break;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getDataType(): string
    {
        return $this->data_type;
    }

    /**
     * @return $this
     */
    public function setDataTypeAsLink()
    {
        $this->dataType('link');

        return $this;
    }

    /**
     * @return $this
     */
    public function setDataTypeAsTsToDate()
    {
        $this->dataType('ts2date');

        return $this;
    }

    public function setDataTypeAsIpLong()
    {
        $this->dataType('iplong');

        return $this;
    }

    public function setDataTypeAsTsToDatetime()
    {
        $this->dataType('ts2datetime');

        return $this;
    }

    public function setDataTypeAsEmail()
    {
        $this->dataType('email');

        return $this;
    }

    public function setDataTypeAsArray()
    {
        $this->dataType('array');

        return $this;
    }

    /**
     * Set number format
     *
     * @param int    $decimals
     * @param string $dec_point
     * @param string $thousands_sep
     *
     * @return $this
     */
    public function setNumberFormat(int $decimals, string $dec_point, string $thousands_sep)
    {
        $this->number_format = [
            'decimals'      => $decimals,
            'dec_point'     => $dec_point,
            'thousands_sep' => $thousands_sep,
        ];

        return $this;
    }

    /**
     *
     * @param int    $row
     * @param array  $row_data
     * @param Linker $linker
     *
     * @return string
     */
    public function getView(int $row, array $row_data, Linker $linker): string
    {
        // Calculate total for column
        if ($this->enable_auto_total_in_column) {
            $this->filter_sum += (float)$row_data[$this->key];
        }

        $value = $this->getCellData($row_data);

        if ($this->enable_encryption && Entity::isFieldEncrypted($value)) {
            $value = SimpleCrypto::decrypt($value, Entity::getEncryptionCheckSumKey());
        }

        // Format value by data type
        switch ($this->data_type) {
            default:

                break;

            case 'email':
                $value = '<a href="mailto:' . $value . '" class="nounderline">' . $value . '</a>';
                break;

            case 'link':
                $value = '<a target="_blank" href="' . $value . '" class="nounderline">' . $value . '</a>';
                break;

            case 'truefalse':
                $value = '<span style="color:' . ($value ? 'green' : 'red') . ';font-size:20px;line-height:10px">&bull;</span>';
                break;

            case 'ts2date':
                $value = $value ? date(CFG_CMS_DATE_FORMAT, (int)$value) : '';
                break;

            case 'datetime2date':
                $value = date('Y-m-d', (int)strtotime($value));
                break;

            case 'number':
                $value = number_format($value, $this->number_format['decimals'], $this->number_format['dec_point'], $this->number_format['thousands_sep']);
                break;

            case 'iplong':
                $value = long2ip($value);
                break;

            case 'ts2datetime':
                $value = $value ? date(CFG_CMS_DATETIME_FORMAT, (int)$value) : '';
                break;

            case 'array':
                $value = $value ? @json_decode($value, true) : '';

                if ($value && is_array($value)) {
                    $tmp = '';

                    foreach ($value as $k => $v) {
                        if (!is_string($v)) {
                            $v = serialize($v);
                        }

                        $tmp[] = '"' . $k . '" : "' . $v . '"';
                    }

                    $value = implode("\n<br>", $tmp);
                }
                break;
        }

        if ($this->href && $this->data_type !== 'mail') {
            $cell_view = $this->getHrefView($value, $this->getHref($row_data, $linker));
        } else {
            $cell_view = $value;
        }

        return $this->getCellView($cell_view, $row_data);
    }
}