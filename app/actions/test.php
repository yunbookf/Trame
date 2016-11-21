<?php

namespace T\Action;

use \T\Service as service;

/**
 * URI: /*
 */
class DevTest extends IAction {

    public function main(array $args): int {

        echo 'testing';

        return 0;

    }
}

return DevTest::class;
