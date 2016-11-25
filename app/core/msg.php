<?php
declare (strict_types = 1);

namespace T\Msg;

use \T\Service\Logger;

abstract class IMessage extends \Exception {

    protected $callPosition;

    public function __construct(string $message = '', int $errorCode = 0) {

        $this->callPosition = getCallerLine();

        parent::__construct($message, $errorCode);
    }

    protected function log(string $type) {

        Logger::write($type, Logger::FETAL_ERROR, <<<MSG
{$this->getMessage()}
***********************
{$this->getTraceAsString()}
MSG
        , $this->callPosition);
    }

    abstract public function handle(\T\HTTP\Request $req, \T\HTTP\Response $resp);
}
