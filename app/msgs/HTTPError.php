<?php
declare(strict_types = 1);

namespace T\Msg;

use T\Msg\IMessage, \T\HTTP as http;

class HTTPError extends IMessage {

    public function handle(http\Request $req, http\Response $resp) {

        $resp->sendError($this->getCode());
    }
}
