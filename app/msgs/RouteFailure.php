<?php
declare(strict_types = 1);

namespace T\Msg;

use T\Core\IMessage, \T\HTTP as http;

class RouteFailure extends IMessage {

    public function handle(http\Request $req, http\Response $resp) {

    }
}
