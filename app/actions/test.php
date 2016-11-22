<?php

namespace T\Action;

use \T\Service as service, \T\Msg as msg;

/**
 * URI: /*
 */
class DevTest extends IAction {

    public function main(array $args): int {

        var_dump($this->request->acceptedLanguages);
        return 0;
    }
}

return DevTest::class;
