<?php

namespace T\Msg;

use T\Core\IMessage, \T\HTTP as http;

class SQLFailure extends IMessage {

    public function handle(http\Request $req, http\Response $resp) {

        \T\Service\Logger::write('sql.failure', $this->getMessage());
        $resp->sendError(http\INTERNAL_ERROR);
    }
}
