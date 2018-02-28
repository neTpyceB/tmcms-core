<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms;

use InvalidArgumentException;

\defined('INC') or exit;

/**
 * Class HtmlAttributes
 */
class HtmlAttributes
{
    private $attr = [];

    /**
     * @return $this
     */
    public static function getInstance()
    {
        return new self();
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return $this|null
     * @throws InvalidArgumentException
     */
    public function __call(string $name, array $arguments)
    {
        $arg_count = \count($arguments);

        if ($arg_count > 1) {
            throw new InvalidArgumentException('Invalid parameter count');
        }

        if ($arg_count) {
            return $this->setAttr($name, $arguments[0]);
        }

        return $this->getAttr($name);
    }

    /**
     * @param string $name
     * @param string $value
     *
     * @return $this
     */
    public function setAttr(string $name, string $value)
    {
        $this->attr[$name] = $value;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return string|null
     */
    public function getAttr($name)
    {
        return $this->attr[$name] ?? null;
    }

    /**
     * @param string $name
     * @param string $value
     * @param string $separator
     *
     * @return $this
     */
    public function appendAttr(string $name, string $value, string $separator = ';')
    {

        if (isset($this->attr[$name])) {
            $this->attr[$name] .= ($separator . $value);
        } else {
            $this->attr[$name] = $value;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $result = '';
        foreach ($this->attr as $name => $value) {
            $result .= ($name . '="' . $value . '" ');
        }

        return $result;
    }
}
