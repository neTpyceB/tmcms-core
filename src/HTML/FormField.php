<?php

namespace TMCms\HTML;

defined('INC') or exit;

abstract class FormField extends Element
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return string
     */
    public function getPlaceholder()
    {
        return $this->getAttribute('placeholder');
    }

    /**
     * @param string $placeholder
     * @return $this
     */
    public function setPlaceholder($placeholder)
    {
        $this->setAttribute('placeholder', $placeholder);

        return $this;
    }

    /**
     * @return string
     */
    public function getRequired()
    {
        return $this->getAttribute('required');
    }

    /**
     * @param string $required
     * @return $this
     */
    public function setRequired($required)
    {
        $this->setAttribute('required', $required);

        return $this;
    }

    /**
     * @return string
     */
    public function getAutofocus()
    {
        return $this->getAttribute('autofocus');
    }

    /**
     * @param string $autofocus
     * @return $this
     */
    public function setAutofocus($autofocus)
    {
        $this->setAttribute('autofocus', $autofocus);

        return $this;
    }
}