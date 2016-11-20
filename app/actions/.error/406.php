<?php
namespace T\Action;

/**
 * URI: /
 */

class HTTPError406 extends IAction {

    public function main(array $args): int {
        header('HTTP/1.1 406 NOT ACCEPTABLE');
        return 0;
    }
}

return HTTPError406::class;
