<?php
namespace T\Action;

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
