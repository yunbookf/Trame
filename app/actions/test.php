<?php

namespace T\Action;

use \T\Service as service, \T\Msg as msg, \T\Model as model;

/**
 * @property \T\Model\UserFactory $users
 *     用户模型工厂（首次调用时分配）
 */
class DevTest extends IAction {

    public function main(array $args): int {

        echo file_get_contents('http://trame.local.org/');
        return 0;
    }
}

return DevTest::class;
