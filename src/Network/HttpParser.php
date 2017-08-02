<?php
declare(strict_types=1);

namespace TMCms\Network;

use RuntimeException;
use TMCms\Cache\Cacher;

defined('INC') or exit;

/**
 * Class HTTP2
 */
class HttpParser
{
    /**
     * In bytes
     * @var int
     */
    private $block_size = 8192;

    /** Seconds
     * @var int
     */
    private $cache_ttl = 86400;
    private $max_connect_attempts = 3;
    private $max_restore_block_attempts = 2;
    private $parsed_url;
    private $headers = [];
    private $headers_is_post = false;
    private $http_version = '1.0';
    private $http_port;
    private $timeout;
    private $query_http_headers = false;
    private $cache_header_key;
    private $cache_body_key;
    private $opened_socket;
    private $fetched_length = 0;
    private $result_plain_headers = false;
    private $result_formatted_headers = false;
    private $result_content_part = false;
    private $result_content = false;

    /**
     * @param string $url
     * @param int    $timeout
     *
     * @throws RuntimeException
     */
    public function __construct(string $url, $timeout = 5)
    {
        $parsed_url = parse_url($url);
        if (!$parsed_url || !isset($parsed_url['scheme'], $parsed_url['host'])) {
            throw new RuntimeException('Wring URL supplied: "' . $url . '"');
        }

        $parsed_url['scheme'] = strtolower($parsed_url['scheme']);
        $this->parsed_url = $parsed_url;
        $this->timeout = (float)$timeout;

        $this->http_port = getservbyname($parsed_url['scheme'], 'tcp');
        if (!$this->http_port) {
            throw new RuntimeException('Can not get port by scheme: "' . $parsed_url['scheme'] . '"');
        }

        $url_md5 = md5($url);
        $this->cache_header_key = $url_md5 . '_header';
        $this->cache_body_key = $url_md5 . '_content';

        $this
            ->setHeadersParam('Host', $parsed_url['host'])
            ->setHeadersParam('Connection', 'Close')
            ->setHeadersUserAgent('Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.2) Gecko/20100316 Firefox/3.6.2');
    }

    /**
     * @param string $user_agent
     *
     * @return HttpParser
     */
    public function setHeadersUserAgent(string $user_agent): HttpParser
    {
        $this->setHeadersParam('User-Agent', $user_agent);

        return $this;
    }

    /**
     * @param string $param
     * @param string $value
     *
     * @return HttpParser
     */
    public function setHeadersParam(string $param, string $value): HttpParser
    {
        $this->headers[$param] = $value;

        return $this;
    }

    /**
     * @param string $data
     *
     * @return string
     */
    public static function getHeaders(string $data): string
    {
        $pos = strpos($data, "\r\n\r\n");

        return $pos === false ? '' : substr($data, 0, $pos);
    }

    /**
     * @param string $header
     *
     * @return string
     */
    public static function getHeaderStatus(string $header): string
    {
        $pos = strpos($header, "\r\n");

        return $pos === false ? '' : substr($header, 0, $pos);
    }

    /**
     * @param string $headers
     *
     * @return array
     */
    public static function parseHeaders(string $headers): array
    {
        $res = [];

        foreach (explode("\r\n", $headers) as $line) {
            $line = trim($line);
            if (!$line) {
                continue;
            }

            $line = explode(': ', $line);
            if (count($line) !== 2) {
                continue;
            }

            $res[strtolower($line[0])] = $line[1];
        }

        return $res;
    }

    /**
     * @param string $headers
     * @param string $status
     *
     * @return bool
     */
    public static function checkStatusFound(string $headers, string $status): bool
    {
        return (bool)preg_match('/HTTP\/[0-9\.]+ ' . $status . ' OK/i', $headers);
    }

    /**
     * @param string $headers
     *
     * @return string
     */
    public static function getStatus(string $headers): string
    {
        if (!preg_match('/HTTP\/[0-9.]+ (\d{3}) OK/i', $headers, $res)) {
            return '';
        }

        return $res[1][0] ?? '';
    }

    /**
     * @param string $headers
     *
     * @return bool
     */
    public static function isLocationHeader(string $headers): bool
    {
        preg_match_all('/location: (.+)/i', $headers, $res);

        return $res[1][0] ?? false;
    }

    /**
     * @param string $data
     *
     * @return string|bool
     */
    public static function extractBody(string $data)
    {
        $pos = strpos($data, "\r\n\r\n");

        return $pos === false ? false : substr($data, $pos + 2);
    }

    /**
     * @param int $version
     *
     * @throws RuntimeException
     */
    public function setHTTPVersion(int $version)
    {
        $this->http_version = ($version === 1 ? '1.0' : '' . $version);

        if ($this->http_version !== '1.0' && $this->http_version !== '1.1') {
            throw new RuntimeException('HTTP version  must be 1.0 or 1.1');
        }
    }

    /**
     * @param int $attempts
     *
     * @return HttpParser
     */
    public function setMaxConnectionAttempts(int $attempts): HttpParser
    {
        $this->max_connect_attempts = $attempts;

        return $this;
    }

    /**
     * @param int $ttl
     *
     * @return HttpParser
     */
    public function setCacheTtl(int $ttl): HttpParser
    {
        $this->cache_ttl = $ttl;

        return $this;
    }

    /**
     * @param int $bytes
     *
     * @return HttpParser
     * @throws RuntimeException
     */
    public function setBlockSize(int $bytes): HttpParser
    {
        $this->block_size = $bytes;

        if ($this->block_size > 8192 || $this->block_size < 1) {
            throw new RuntimeException('Block must not be higher than 8KB');
        }

        return $this;
    }

    /**
     * @param array $cookies
     *
     * @return HttpParser
     */
    public function setCookies(array $cookies): HttpParser
    {
        $this->setHeadersParam('Cookie', http_build_cookie($cookies));

        return $this;
    }

    /**
     * @param $post
     *
     * @return HttpParser
     */
    public function setPostData($post): HttpParser
    {
        if (is_array($post)) {
            $post = http_build_query($post);
        }

        $this
            ->setHeadersParam('Content-Type', 'application/x-www-form-urlencoded')
            ->setHeadersParam('Content-Length', (string)strlen($post));

        $this->headers_is_post = $post;

        return $this;
    }

    /**
     * @param array $headers
     *
     * @return HttpParser
     */
    public function setHeaders(array $headers): HttpParser
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPlainHeaders()
    {
        // Check cache
        $headers = $this->getCachedPlainHeaders();
        if ($headers) {
            return $headers;
        }

        // Fetch real
        $this->fetchHeaders();

        return $this->result_plain_headers;
    }

    /**
     * @return mixed
     */
    private function getCachedPlainHeaders()
    {
        return Cacher::getInstance()->getDefaultCacher()->get($this->cache_header_key);
    }

    /**
     * @param bool $format
     *
     * @return bool
     */
    private function fetchHeaders(bool $format = false): bool
    {
        $this->refreshQueryHeaders();
        if (!$this->prepareSocket()) {
            return false;
        }

        $this->fetched_length = 0;
        $res = '';
        $prev_blocks = '';
        // Read
        while (!feof($this->opened_socket)) {
            $current_block = $this->getBlock();
            if ($current_block === false) {
                return false;
            }
            $current_block_with_prev = $prev_blocks . $current_block;
            $header_end_pos = $this->getHeadersBreakPos($current_block_with_prev);

            if ($header_end_pos !== false) {
                $res .= substr($current_block_with_prev, 0, $header_end_pos);
                $this->result_content_part = substr($current_block_with_prev, $header_end_pos + 4);
                break;
            }

            // Else
            $res .= $current_block;

            $prev_blocks = $current_block;
        }

        if ($res) {
            $this->result_plain_headers = $res;
            $this->cachePlainHeaders();

            if ($format) {
                $this->result_formatted_headers = $this->prepareHeaderData($res);
            }
        }

        return true;
    }

    /**
     * @return HttpParser
     */
    private function refreshQueryHeaders(): HttpParser
    {
        $string = ($this->headers_is_post ? 'POST' : 'GET') . ' ';
        $string .= $this->parsed_url['path'] ?? '';
        $string .= (isset($this->parsed_url['query']) ? '?' . $this->parsed_url['query'] : '');
        $string .= (isset($this->parsed_url['fragment']) ? '#' . $this->parsed_url['fragment'] : '');
        $string .= ' HTTP/' . $this->http_version . "\r\n";

        $res = [];
        // Glue
        foreach ($this->headers as $k => $v) {
            $res[] = $k . ': ' . $v;
        }

        $string .= implode("\r\n", $res) . "\r\n\r\n";
        if ($this->headers_is_post) {
            $string .= $this->headers_is_post;
        }

        $this->query_http_headers = $string;

        return $this;
    }

    /**
     * @return resource|bool
     */
    private function prepareSocket()
    {
        if (!$this->opened_socket) {
            $host = ($this->parsed_url['scheme'] === 'https' ? 'ssl://' : '') . $this->parsed_url['host'];
            $attempts = $this->max_connect_attempts;

            $socket = NULL;
            while ($attempts) {
                $socket = fsockopen($host, $this->http_port, $error_number, $error_string, $this->timeout);
                if ($socket) {
                    break;
                }
                $attempts--;
            }

            if (!$socket) {
                return false;
            }

            if (fwrite($socket, $this->query_http_headers) === false) {
                return false;
            }

            $this->fetched_length = 0;
            $this->opened_socket = $socket;
        }

        return $this->opened_socket;
    }

    /**
     * @return bool|string
     */
    private function getBlock()
    {
        $block = fread($this->opened_socket, $this->block_size);
        if ($block === false) {
            $block = $this->tryToRestore($this->max_restore_block_attempts);
            if ($block === false) {
                return false;
            }
            $this->fetched_length += strlen($block);
        } else {
            $this->fetched_length += strlen($block);
        }

        return $block;
    }

    /**
     * @param int $attempt
     *
     * @return bool|string
     */
    private function tryToRestore(int $attempt)
    {
        if (!$attempt) {
            return false;
        }
        $this->opened_socket = NULL;
        if (!$this->prepareSocket()) {
            return false;
        }

        $len_restored = $restored_block = 0;
        while ($len_restored < $this->fetched_length) {
            $restored_block = fread($this->opened_socket, $this->block_size);
            if ($restored_block === false) {
                return $this->tryToRestore($attempt - 1);
            }
            $len_restored += strlen($restored_block);
        }

        if ($len_restored > $this->fetched_length) {
            $last_block_len = $this->block_size - ($len_restored - $this->fetched_length);
            $block = substr($restored_block, $last_block_len);
        } else {
            $block = fread($this->opened_socket, $this->block_size);
            if ($block === false) {
                return $this->tryToRestore($attempt - 1);
            }
        }

        return $block;
    }

    /**
     * @param string $result_part
     *
     * @return int
     */
    private function getHeadersBreakPos(string $result_part): int
    {
        return strpos($result_part, "\r\n\r\n");
    }

    /**
     * @return HttpParser
     */
    private function cachePlainHeaders(): HttpParser
    {
        if ($this->result_plain_headers && $this->cache_ttl) {
            Cacher::getInstance()->getDefaultCacher()->set($this->cache_header_key, $this->result_plain_headers, $this->cache_ttl);
        }

        return $this;
    }

    /**
     * @param string $src
     *
     * @return array
     */
    private function prepareHeaderData(string $src): array
    {
        $header_sep_position = strpos($src, "\r\n");

        $tmp = explode('/', $this->parsed_url['path'] ?? '');
        $path_lst_fragment = end($tmp);

        $res = [
            'status_raw'   => substr($src, 0, $header_sep_position),
            'headers_raw'  => $src,
            'headers'      => [],
            'content-type' => '',
            'status_code'  => '',
        ];

        if (substr($path_lst_fragment, -1) !== '/' && strpos($path_lst_fragment, '.') !== false) {
            $ext_tmp = explode('.', $path_lst_fragment);
            $res['extension'] = strtolower(end($ext_tmp));
        } else {
            $res['extension'] = '';
        }

        foreach (explode("\r\n", $res['headers_raw']) as $v) {
            if (!$v) {
                continue;
            }

            $v = explode(':', $v);
            if (count($v) < 2) {
                continue;
            }

            $res['headers'][strtolower(array_shift($v))] = trim(implode(':', $v));
        }

        if (isset($res['headers']['content-type']) && $res['headers']['content-type']) {
            preg_match_all('/^[a-z\/\-]+/', $res['headers']['content-type'], $cont_type);

            if (isset($cont_type[0][0]) && $cont_type[0][0]) {
                $res['content-type'] = $cont_type[0][0];
            }
        }

        $status = explode(' ', $res['status_raw']);
        if (isset($status[1]) && strlen($status[1]) === 3 && ctype_digit($status[1])) {
            $res['status_code'] = (int)$status[1];
        }

        return $res;
    }

    /**
     * @return array|bool
     */
    public function getFormattedHeaders()
    {
        $headers = $this->getCachedPlainHeaders();
        if ($headers) {
            return $this->prepareHeaderData($headers);
        }

        $this->fetchHeaders(true);

        return $this->result_formatted_headers;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        $content = $this->getCachedContent();
        if ($content) {
            return $content;
        }

        if (!$this->result_plain_headers && !$this->fetchHeaders()) {
            return false;
        }

        $body = $this->result_content_part;

        while (!feof($this->opened_socket)) {
            $block = $this->getBlock();
            if ($block === false) {
                return false;
            }
            $body .= $block;
        }

        $this->result_content = $body;
        $this->cacheContent();

        return $body;
    }

    /**
     * @return mixed
     */
    private function getCachedContent()
    {
        return Cacher::getInstance()->getDefaultCacher()->get($this->cache_body_key);
    }

    /**
     * @return HttpParser
     */
    private function cacheContent(): HttpParser
    {
        if ($this->result_content && $this->cache_ttl) {
            Cacher::getInstance()->getDefaultCacher()->set($this->cache_body_key, $this->result_content, $this->cache_ttl);
        }

        return $this;
    }

    public function __destruct()
    {
        if ($this->opened_socket) {
            fclose($this->opened_socket);
        }
    }

    /**
     * @param string $src
     * @param int    $headers_body_sep_pos
     * @param bool   $reverse_chunk
     *
     * @return bool|string
     */
    public function prepareBodyData(string $src, int $headers_body_sep_pos, bool $reverse_chunk)
    {
        $result = substr($src, $headers_body_sep_pos + 4);

        if ($reverse_chunk) {
            return $this->reverse_chunk($result);
        }

        return $result;
    }

    /**
     * Makes one boyd string from chunks
     *
     * @param string $s
     *
     * @return string
     */
    private function reverse_chunk($s): string
    {
        $res = '';

        while (strlen($s)) {
            $pos = strpos($s, "\r\n");

            if ($pos === false) {
                $res .= $s;
                break;
            }

            $l = hexdec(substr($s, 0, $pos));
            $res .= substr($s, $pos + 2, $l);
            $s = substr($s, $l + $pos + 2);
        }

        return $res;
    }
}