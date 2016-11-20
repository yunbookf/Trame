<?php
namespace T\Action;

/**
 * URI: /
 */

class HTTPError501 extends IAction {

    public function main(array $args): int {
        header('HTTP/1.1 501 NOT IMPLEMENTED');
        return 0;
    }
}

return HTTPError501::class;
