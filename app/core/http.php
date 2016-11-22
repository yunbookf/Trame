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

            } else {

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

    /**
     * Return the raw content of HTTP request.
     * @param bool $emitError
     * @throws \T\Msg\HTTPError
     * @return string
     */
    public function getRawContent(bool $emitError = true): string {

        $data = @file_get_contents('php://input');

        if ($data === false && $emitError) {

            throw new \T\Msg\HTTPError(
                'Failed to read JSON from HTTP content.',
                \T\HTTP\NOT_ACCEPTABLE
            );
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

            throw new \T\Msg\HTTPError(
                'Failed to read JSON from HTTP content.',
                \T\HTTP\NOT_ACCEPTABLE
            );
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
     * For this, DO NEVER
     * USE IT FOR SECURITY JUDGING!!!
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
            
            return self::CLIENT_IP;
        }
    
    }

}
