<?php
namespace T\Action;

/**
 * URI: /forums/
 */

class HomePage extends IAction {

    public function main(array $args) {
        echo 'hello';
    }
}

return HomePage::class;
