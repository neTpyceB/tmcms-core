<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms\Element;

use TMCms\HTML\Element;

\defined('INC') or exit;

/**
 * Class CmsRow
 * @package TMCms\HTML\Cms\Element
 */
class CmsRow extends Element {
    protected $value = '';

    /**
     * @param string $name
     */
    public function __construct(string $name) {
        parent::__construct();

        $this->setName($name);
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public static function getInstance(string $name) {
        return new self($name);
    }

    /**
     * @return string
     */
    public function __toString() {
        return $this->getValue();
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
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }
}
