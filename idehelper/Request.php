<?php
declare(strict_types = 1);

namespace T\HTTP;

class Request {

    /**
     * Remote address of the remote HTTP client.
     *
     * @var string
     */
    public $ip;
    
    /**
     * The name and version of the remote HTTP client.
     *
     * @var string
     */
    public $userAgent;
    
    /**
     * The hostname of this server.
     *
     * @var string
     */
    public $host;
    
    /**
     * The bytes length of request content.
     *
     * @var int
     */
    public $contentLength;
    
    /**
     * The MIME type of request content.
     *
     * @var string
     */
    public $contentType;
    
    /**
     * The acceptable languages of HTTP client.
     *
     * @var string[]
     */
    public $acceptedLanguages;
    
}
