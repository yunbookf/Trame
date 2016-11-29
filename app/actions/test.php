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

        $this->response->setContentType('text/palin');
        echo $this->db->sql('test', function(\T\TDBI\SQLBuilder $sql) {
            $sql->from('test')->insert(['id', 'married', 'name', 'friend'])->values([
                'id' => 123,
                'friend' => null,
                'married' => true,
                'name' => 'yubo'
            ])->values([
                'id' => 333,
                'friend' => null,
                'married' => false,
                'name' => 'John'
            ])->end();
        })->getSQL();
        return 0;
    }
}

return DevTest::class;
