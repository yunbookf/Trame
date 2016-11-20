<?php
namespace T\Action;

/**
 * URI: /
 */

class HTTPError401 extends IAction {

    public function main(array $args): int {
        header('HTTP/1.1 401');
        return 0;
    }
}

return HTTPError401::class;
