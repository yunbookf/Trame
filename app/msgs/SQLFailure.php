<?php

namespace T\Msg;

use T\Core\IMessage;

class SQLFailure extends IMessage {

    public function handle() {

        header('HTTP/1.1 500 INTERNAL ERROR'); // 显示 HTTP 500 错误

        \T\Service\Logger::write('sql', $this->getMessage());

    }

}
