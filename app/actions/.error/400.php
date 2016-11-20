<?php
namespace T\Action;

/**
 * URI: /
 */

class HTTPError400 extends IAction {

    public function main(array $args): int {
        header('HTTP/1.1 400 BAD REQUEST');
        return 0;
    }
}

return HTTPError400::class;
