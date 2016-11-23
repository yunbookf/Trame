<?php

namespace T\Msg;

use T\Core\IMessage, \T\HTTP as http;

class CacheFailure extends IMessage {

    public function handle(http\Request $req, http\Response $resp) {

        $this->log('cache.failure');

        $resp->sendError(http\INTERNAL_ERROR);
    }
}
