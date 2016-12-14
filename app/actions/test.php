<?php

namespace T\Action;

use \T\Service as service, \T\Msg as msg, \T\Model as model;

/**
 * @property \T\Model\UserFactory $users
 *     用户模型工厂（首次调用时分配）
 */
class DevTest extends IAction {

    public function main(array $args): int {
        $g = fsockopen('127.0.0.1', 80, $errno, $errstr, 10);
        fwrite($g, "GET / HTTP/1.1\r
HOST: trame.local.org\r
\r
"
        );
        echo fread($g, 2048);
        fclose($g);
        return 0;
    }
}

return DevTest::class;
