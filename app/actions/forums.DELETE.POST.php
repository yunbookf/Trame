<?php
namespace T\Action;

/**
 * URI: /
 */

class HomePage extends IAction {

    public function main(array $args) {
        echo 'hello';
    }
}

return HomePage::class;
