<?php
declare(strict_types = 1);

namespace T\HTTP;

const BAD_REQUEST = 400;

const UNAUTHORIZED = 401;

const FORBIDDEN = 403;

const NOT_FOUND = 404;

const METHOD_NOT_ALLOWED = 405;

const NOT_ACCEPTABLE = 406;

const INTERNAL_ERROR = 500;

const NOT_IMPLEMENTED = 501;

const BAD_GATEWAY = 502;

const TEMPORARY_ERROR = 503;

const GATEWAY_TIMEOUT = 504;

/**
 *
 * @property string $ip
 *     客户端 IP（本属性在首次引用时才分配）
 * @property string $userAgent
 *     客户端软件信息（本属性在首次引用时才分配）
 * @property string $host
 *     服务器的 hostname（本属性在首次引用时才分配）
 * @property int $contentLength
 *     请求携带的 HTTP Content 的数据长度（本属性在首次引用时才分配）
 * @property string $contentType
 *     请求携带的 HTTP Content MIME类型（本属性在首次引用时才分配）
 * @property string[] $acceptedLanguages
 *     客户端可以接受的语言信息（本属性在首次引用时才分配）
 */
class Request {

    use \T\Core\TDelayInitializer;

    public function __construct() {

        $this->di = [
            'host' => function() {

                return $_SERVER['HTTP_HOST'] ?? null;
            },
            'contentLength' => function() {

                return $_SERVER['CONTENT_LENGTH'] ?? null;
            },
            'contentType' => function() {

                return $_SERVER['CONTENT_TYPE'] ?? null;
            },
            'userAgent' => function() {

                return $_SERVER['HTTP_USER_AGENT'] ?? null;
            },
            'ip' => function() {

                return $_SERVER['REMOTE_ADDR'] ?? null;
            },
            'acceptedLanguages' => function() {

                if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {

                    $langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
                }
                else {
    
                    $langs = [];
                }

                foreach ($langs as &$lang) {

                    $tmp = explode(';q=', $lang);

                    if (isset($tmp[1])) {

                        $lang = [
                            'language' => $tmp[0],
                            'priority' => $tmp[1] + 0
                        ];
                    } else {

                        $lang = [
                            'language' => $lang,
                            'priority' => 1
                        ];
                    }
                }

                return $langs;
            }
        ];
    }

    /**
     * 获取 POST/PUT/PATCH/DELETE 等请求的携带的 HTTP Content 裸数据。
     * 
     * @param bool $emitError
     *     可选参数，默认为 true。如果设为 false，则获取数据失败时不会抛出异常而是返回 null。
     * 
     * @throws \T\Msg\HTTPError
     * @return string
     *     返回 HTTP Content 。如果将参数 $emitError 设为 false，则失败时返回 null。
     */
    public function getRawContent(bool $emitError = true): string {

        $data = @file_get_contents('php://input');

        if ($data === false && $emitError) {

            throw new \T\Msg\HTTPError('Failed to read JSON from HTTP content.', 
                \T\HTTP\NOT_ACCEPTABLE);
        }

        return $data;

    }

    /**
     * 获取 POST/PUT/PATCH/DELETE 等请求的携带的 JSON 数据，并返回解码后的对象。
     * 
     * @param bool $emitError
     *     可选参数，默认为 true。如果设为 false，则获取数据失败时不会抛出异常而是返回 null。
     * 
     * @throws \T\Msg\HTTPError
     * @return any
     *     返回解码后的 JSON 对象。如果将参数 $emitError 设为 false，则失败时返回 null。
     */
    public function getJSONContent(bool $emitError = true) {

        $data = json_decode($this->getRawContent($emitError));

        if ($data === null && $emitError) {

            throw new \T\Msg\HTTPError('Failed to read JSON from HTTP content.', 
                \T\HTTP\NOT_ACCEPTABLE);
        }

        return $data;

    }

    /**
     * 判断 POST/PUT/PATCH/DELETE 等请求的携带的 HTTP Content 是否为 JSON 格式
     * @return bool
     */
    public function isJSONContent(): bool {

        if (!$this->contentType) {

            return false;
        }

        switch ($this->contentType) {
        case 'text/json':
        case 'application/json':

            return true;

        default:

            return false;
        }

    }

    /**
     * （跨越代理获取）获取客户端的真实 IP
     * 
     * 警告：该方法获取的 IP 可以通过发送 HTTP-X-FORWARDED-FOR 头伪造，
     * 因此严禁在安全检测中使用该方法获取IP！
     * 
     * @return string
     */
    public function getIP(): string {

        if (getenv('HTTP_CLIENT_IP')) {

            return getenv('HTTP_CLIENT_IP');
        }
        elseif (getenv('HTTP_X_FORWARDED_FOR')) {

            return getenv('HTTP_X_FORWARDED_FOR');
        }
        else {

            return $this->ip;
        }

    }

    /**
     * This method checks if a WWW-Authentication existing.
     *
     * @return bool return ture if a WWW-Authentication header info exists.
     */
    public function hasAuthentication(): bool {
    
        return isset($_SERVER['HTTP_AUTHORIZATION']);
    
    }
    
    /**
     * This method returns the WWW-Authentication info, as fields username
     * and password in an array.
     *
     * @return array
     */
    public function getAuthentication(): array {
    
        $rtn = [];
        list ($rtn['username'], $rtn['password']) = explode(':',
            base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
        return $rtn;
    
    }
    
}

class Response {

    const HTTP_VERSION_1_0 = 1.0;

    const HTTP_VERSION_1_1 = 1.1;

    public $httpVersion = self::HTTP_VERSION_1_1;

    /**
     * 该方法发送 HTTP 重定向头部信息
     *
     * @param string $url
     *     重定向的目的 URL
     * @param string $escape
     *     可选参数，是否对参数1进行转义编码，默认为 false。
     */
    public function redirect(string $url, bool $escape = false) {

        header('location: ' . ($escape ? urlencode($url) : $url));

    }

    /**
     * 发出 HTTP 错误号
     *
     * @param integer $errno
     */
    public function sendError(int $errno) {

        header("HTTP/{$this->httpVersion} {$errno}");

    }

    /**
     * This method sends a WWW-Authentication request back to client,
     * and sends a HTTP error code 401.
     *
     * @param string $tips
     *     The tips text when authenticating.
     */
    public function authenticate(string $tips) {

        header('WWW-Authenticate: Basic realm="' . $tips . '"');
        $this->raiseError(UNAUTHORIZED);
    }

    /**
     * This method sends the HTTP Content Type header info.
     *
     * @param string $type
     *     The content type to be sent
     */
    public function setContentType(string $type) {

        header('Content-Type: ' . $type);
    }

    /**
     * 输出一句 HTTP 头信息。
     *
     * @param string $head
     */
    public function send(string $head) {

        header($head);

    }

    /**
     * 设置输出的下载文件名。
     *
     * @param string $fn
     *     待设置的文件名
     */
    public function setDownloadName(string $fn) {

        $_SERVER['HTTP_USER_AGENT'] = strtolower($_SERVER['HTTP_USER_AGENT']);

        if (strpos($_SERVER['HTTP_USER_AGENT'], 'msie') !== false ||
            strpos($_SERVER['HTTP_USER_AGENT'], 'trident/7.0') !== false) {

            $fnEnc = str_replace('+', '%20', urlencode($fn));

            header("Content-Disposition: attachment; filename=\"{$fnEnc}\"");
        }
        elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'firefox') !== false) {

            header(<<<HTTP
Content-Disposition: attachment; filename*="utf8''{$fn}"
HTTP
            );
        }
        else {
            header("Content-Disposition: attachment; filename=\"{$fn}\"");
        }
    }
}
