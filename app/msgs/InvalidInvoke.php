<?php
declare(strict_types = 1);

namespace T\Msg;

use T\Core\IMessage, \T\Service\Logger, \T\HTTP as http;

class InvalidInvoke extends IMessage {

    public function handle(http\Request $req, http\Response $resp) {

        Logger::write('bugs', Logger::FETAL_ERROR, $this->__toString());

        $resp->sendError(http\INTERNAL_ERROR);
    }
}
