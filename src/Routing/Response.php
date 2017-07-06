<?php
declare(strict_types=1);

namespace TMCms\Routing;

use TMCms\Strings\Converter;
use TMCms\Traits\singletonInstanceTrait;

defined('INC') or exit;

class Response
{
    use singletonInstanceTrait;

    private $response_code = '200';
    private $response_html = '';
    private $response_finished = false;

    public function __toString(): string
    {
        $this->sendHeaders();

        return (string)$this->response_html;
    }

    private function sendHeaders()
    {
        $status_string = Converter::headerHttpCodeToString($this->response_code);
        header($_SERVER['SERVER_PROTOCOL'] . ' ' . $status_string, true, (int)$status_string);
    }

    public function setHtml($html)
    {
        if ($this->response_finished) {
            return $this;

        }

        $this->response_html = $html;

        return $this;
    }

    public function setHttpCode($code)
    {
        if ($this->response_finished) {
            return $this;

        }

        $this->response_code = $code;

        return $this;
    }

    public function setFinished($flag)
    {
        $this->response_finished = $flag;

        return $this;
    }
}