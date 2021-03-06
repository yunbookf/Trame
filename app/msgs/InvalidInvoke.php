<?php
declare(strict_types = 1);

namespace T\Msg;

use T\Msg\IMessage, \T\HTTP as http;

class InvalidInvoke extends IMessage {

    public function handle(http\Request $req, http\Response $resp) {

        $this->message = "Method {$this->message} doesn't exist.";
        $this->log('bugs');

        $resp->sendError(http\INTERNAL_ERROR);
    }
}
