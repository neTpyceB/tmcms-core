<?php
declare(strict_types=1);

namespace TMCms\HTML;

defined('INC') or exit;

/**
 * Class Form
 */
abstract class Form
{
    const ENCTYPE_MULTIPART = 'multipart/form-data';
    const ENCTYPE_URLENCODED = 'application/x-www-form-urlencoded';
    const METHOD_GET = 'get';
    const METHOD_POST = 'post';

    protected $method = self::METHOD_POST;
    protected $enctype = self::ENCTYPE_URLENCODED;
    protected $id;
    protected $action;
    protected $fields = [];

    public static function getInstance()
    {
        return new static();
    }

    /**
     * @param string  $title
     * @param Element $field
     *
     * @return $this
     */
    public function addField(string $title, Element $field)
    {
        $this->fields[] = new FormElement($title, $field);

        return $this;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setId(string $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param string $action
     *
     * @return $this
     */
    public function setAction(string $action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param string $method
     *
     * @return $this
     */
    public function setMethod(string $method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @return string
     */
    public function getEnctype(): string
    {
        return $this->enctype;
    }

    /**
     * @param string $enctype
     *
     * @return $this
     */
    public function setEnctype(string $enctype)
    {
        $this->enctype = $enctype;

        return $this;
    }

    /**
     * @return $this
     */
    public function setEnctypeMultipartFormData()
    {
        $this->enctype = self::ENCTYPE_MULTIPART;

        return $this;
    }

    abstract public function __toString();
}