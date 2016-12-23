<?php

namespace T\Action;

use \T\Service as service, \T\Msg as msg, \T\Model as model;

/**
 * @property \T\Model\UserFactory $users
 *     用户模型工厂（首次调用时分配）
 */
class DevTest extends IAction {

    public function __construct() {

        parent::__construct();

        $this->di['users'] = function() {
            return model\ModelFactory::getFactory('User');
        };
    }

    public function main(array $args): int {

        $user = $this->users->get(1);
        echo $user->email;

        return 0;
    }
}

return DevTest::class;
