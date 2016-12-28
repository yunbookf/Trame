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

        $sql = $this->db->sql('test', function(\T\TDBI\SQLBuilder $sql) {
            $sql->insert(['a', 'b'])->into('tests')->end();
        });
        var_dump((string)$sql->multiValues([
            [
                'b' => 'aaa',
                'a' => 123
            ],[
                'a' => 123,
                'b' => 'aaa'
            ]
        ]));

        return 0;
    }
}

return DevTest::class;
