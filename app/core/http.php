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
 *     Remote address of the remote HTTP client.
 * @property string $userAgent
 *     The name and version of the remote HTTP client.
 * @property string $host
 *     The hostname of this server.
 * @property int $contentLength
 *     The bytes length of request content.
 * @property string $contentType
 *     The MIME type of request content.
 * @property string[] $acceptedLanguages
 *     The acceptable languages of HTTP client.
 */
class Request {

    public function __get(string $name) {

        switch ($name) {
        case 'host':

            return $this->host = $_SERVER['HTTP_HOST'] ?? null;

        case 'contentLength':

            return $this->contentLength = $_SERVER['CONTENT_LENGTH'] ?? null;

        case 'contentType':

            return $this->contentType = $_SERVER['CONTENT_TYPE'] ?? null;

        case 'userAgent':

            return $this->userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        case 'ip':

            return $this->ip = $_SERVER['REMOTE_ADDR'] ?? null;

        case 'acceptedLanguages':

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
                }
            }

            return $langs;
        }

    }

    public function __isset(string $name) {

        switch ($name) {
        case 'ip':
        case 'userAgent':
        case 'host':
        case 'contentLength':
        case 'contentType':
        case 'acceptedLanguages':

            return true;

        default:

            return false;
        }
    }

    /**
     * Return the raw content of HTTP request.
     * @param bool $emitError
     * @throws \T\Msg\HTTPError
     * @return string
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
     *
     * @param bool $emitError
     * @throws \T\Msg\HTTPError
     * @return mixed
     */
    public function getJSONContent(bool $emitError = true) {

        $data = json_decode($this->getRawContent($emitError));

        if ($data === null && $emitError) {

            throw new \T\Msg\HTTPError('Failed to read JSON from HTTP content.', 
                \T\HTTP\NOT_ACCEPTABLE);
        }

        return $data;

    }

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
     * !!!NOTICE: This method may be cheated by sending
     * HTTP-X-FORWARDED-FOR header.
     * For this, DO NEVER USE IT FOR SECURITY JUDGING!!!
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

}

class Response {

    const HTTP_VERSION_1_0 = 1.0;

    const HTTP_VERSION_1_1 = 1.1;

    public $httpVersion = self::HTTP_VERSION_1_1;

    /**
     * This method sends a header to tell client redirecting to a URL.
     *
     * @param string $url
     *     The target URL to redirect
     * @param string $escape
     *     Whether should encode this URL.
     */
    public function redirect($url, $escape = false) {

        header('location: ' . ($escape ? urlencode($url) : $url));

    }

    /**
     * Send a HTTP error status code.
     *
     * @param integer $errno
     */
    public function sendError($errno) {

        header("HTTP/{$this->httpVersion} {$errno}");

    }

    /**
     * This method sends a WWW-Authentication request back to client,
     * and sends a HTTP error code 401.
     *
     * @param string $tips
     *     The tips text when authenticating.
     */
    public function authenticate($tips) {

        header('WWW-Authenticate: Basic realm="' . $tips . '"');
        $this->raiseError(UNAUTHORIZED);

    }

    /**
     * This method checks if a WWW-Authentication existing.
     *
     * @return bool return ture if a WWW-Authentication header info exists.
     */
    public function hasAuthentication() {

        return isset($_SERVER['HTTP_AUTHORIZATION']);

    }

    /**
     * This method returns the WWW-Authentication info, as fields username
     * and password in an array.
     *
     * @return array
     */
    public function getAuthentication() {

        $rtn = [];
        list ($rtn['username'], $rtn['password']) = explode(':',
            base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
        return $rtn;

    }

    /**
     * This method sends the HTTP Content Type header info.
     *
     * @param string $type
     *     The content type to be sent
     */
    public function setContentType($type) {

        header('Content-Type: ' . $type);

    }

    /**
     * This method sends a raw HTTP header info.
     *
     * @param string $head
     */
    public function send(string $head) {

        header($head);

    }

    /**
     * This method helps setup the filename for HTTP download.
     *
     * @param string $fn
     *     The target name to be output.
     */
    public function setDownloadName($fn) {

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
