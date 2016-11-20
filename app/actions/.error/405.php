<?php
namespace T\Action;

/**
 * URI: /
 */

class HTTPError406 extends IAction {

    public function main(array $args): int {
        header('HTTP/1.1 405 METHOD NOT ALLOWED');
        return 0;
    }
}

return HTTPError406::class;
