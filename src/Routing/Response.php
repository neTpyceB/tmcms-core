<?php
declare(strict_types=1);

namespace TMCms\Routing;

use TMCms\Strings\Converter;
use TMCms\Traits\singletonInstanceTrait;

defined('INC') or exit;

/**
 * Class Response
 * @package TMCms\Routing
 */
class Response
{
    use singletonInstanceTrait;

    private $response_code = 200;
    private $response_html = '';
    private $response_finished = false;

    /**
     * @return string
     */
    public function __toString(): string
    {
        $this->sendHeaders();

        return $this->response_html;
    }

    private function sendHeaders()
    {
        $status_string = Converter::headerHttpCodeToString($this->response_code);
        header(SERVER_PROTOCOL . ' ' . $status_string, true, (int)$status_string);
    }

    /**
     * @param string $html
     *
     * @return Response
     */
    public function setHtml(string $html): Response
    {
        if ($this->response_finished) {
            return $this;

        }

        $this->response_html = $html;

        return $this;
    }

    /**
     * @param int $code
     *
     * @return Response
     */
    public function setHttpCode(int $code): Response
    {
        if ($this->response_finished) {
            return $this;

        }

        $this->response_code = $code;

        return $this;
    }

    /**
     * @param bool $flag
     *
     * @return Response
     */
    public function setFinished(bool $flag): Response
    {
        $this->response_finished = $flag;

        return $this;
    }
}
