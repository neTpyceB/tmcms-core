<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Element;

use TMCms\HTML\Element;

defined('INC') or exit;

/**
 * Class CmsHtml
 */
class CmsHtml extends Element
{
    protected $value = '';

    /**
     * @param $name
     */
    public function __construct(string $name)
    {
        parent::__construct();

        $this->setName($name);
    }

    /**
     * @param string $name
     *
     * @return CmsHtml
     */
    public static function getInstance(string $name)
    {
        return new self($name);
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setValue(string $value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->value;
    }
}