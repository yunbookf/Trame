<?php
declare(strict_types = 1);

namespace T\Msg;

use T\Core\IMessage;

class HTTPError extends IMessage {

    public function handle() {

        header('HTTP/1.1 ' . $this->getCode());
    }

}
