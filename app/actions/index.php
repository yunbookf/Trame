<?php
namespace T\Action;

/**
 * URI: /
 */

class HomePage extends IAction {

    public function main(array $args): int {
        echo 'hello';
        return 0;
    }
}

return HomePage::class;
