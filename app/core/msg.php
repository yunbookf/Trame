<?php
declare (strict_types = 1);

namespace T\Core;

abstract class IMessage extends \Exception {

    public function __construct(string $message = '', int $errorCode = 0) {

        parent::__construct($message, $errorCode);
    }

    abstract public function handle();
}
