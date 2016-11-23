<?php

namespace T\Action;

use \T\Service as service, \T\Msg as msg;

/**
 * URI: /*
 */
class DevTest extends IAction {

    public function main(array $args): int {

        echo $this->ggg;
        return 0;

    }

}

return DevTest::class;
