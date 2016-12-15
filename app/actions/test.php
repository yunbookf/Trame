<?php

namespace T\Action;

use \T\Service as service, \T\Msg as msg, \T\Model as model;

/**
 * @property \T\Model\UserFactory $users
 *     用户模型工厂（首次调用时分配）
 */
class DevTest extends IAction {

    public function main(array $args): int {
        $this->response->setContentType('text/plain');
        echo service\StringUtils::random(32, service\StringUtils::RAND_SRC_CUSTOMIZED, 'abcdef0123456789');
        return 0;
    }
}

return DevTest::class;
