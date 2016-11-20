<?php
namespace T\Action;

/**
 * URI: /
 */

class HTTPError502 extends IAction {

    public function main(array $args): int {
        header('HTTP/1.1 502 BAD GATEWAY');
        return 0;
    }
}

return HTTPError502::class;
