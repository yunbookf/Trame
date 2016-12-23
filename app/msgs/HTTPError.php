<?php
declare(strict_types = 1);

namespace T\Msg;

use T\Msg\IMessage, \T\HTTP as http;

/**
 * 该异常纯粹显示 HTTP 错误页面，不做其它处理。
 */
class HTTPError extends IMessage {

    public function handle(http\Request $req, http\Response $resp) {

        $this->log('http');
        $resp->writeHeader('STATUS', $this->getCode());
    }
}
