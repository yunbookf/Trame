<?php
declare(strict_types = 1);

namespace T\Msg;

use T\Msg\IMessage, \T\HTTP as http;

class InvalidProperty extends IMessage {

    public function handle(http\Request $req, http\Response $resp) {

        $this->message = "Property {$this->message} doesn't exist.";
        $this->log('bugs');

        $resp->sendError(http\INTERNAL_ERROR);
    }
}
