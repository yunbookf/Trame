<?php
declare(strict_types = 1);

namespace T\Msg;

use T\Msg\IMessage, \T\HTTP as http;

class ServiceFailure extends IMessage {

    public function handle(http\Request $req, http\Response $resp) {

        $this->log('bugs.service');

        $resp->sendError(http\INTERNAL_ERROR);
    }
}
