<?php

namespace T\Action;

use \T\Service as service, \T\Msg as msg;

/**
 * URI: /*
 */
class DevTest extends IAction {

    public function main(array $args): int {

        var_dump($this->db->exec('UPDATE `users` SET `seedbonus`=1234567 WHERE id=8017'));
        return 0;
    }
}

return DevTest::class;
