<?php
namespace T\Action;

/**
 * URI: /
 */

class HTTPError404 extends IAction {

    public function main(array $args): int {
        header('HTTP/1.1 404 NOT FOUND');
        return 0;
    }
}

return HTTPError404::class;
