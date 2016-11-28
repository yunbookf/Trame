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
            $sql->update()->from('users')->join('ratios')->on([
                'ratios.type' => 'dollar2rmb'
            ])->set([
                '@rmb' => 'dollar * ratios.ratio',
                'dollar' => null
            ])->where([
                'id' => 123
            ])->limit(1)->end();
        })->getSQL();
        return 0;
    }
}

return DevTest::class;
