<?php
namespace T\Action;

/**
 * URI: /
 */

class HTTPError500 extends IAction {

    public function main(array $args): int {
        header('HTTP/1.1 500 INTERNAL ERROR');
        return 0;
    }
}

return HTTPError500::class;
