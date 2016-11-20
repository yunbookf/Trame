<?php
namespace T\Action;

/**
 * URI: /
 */

class HTTPError503 extends IAction {

    public function main(array $args): int {
        header('HTTP/1.1 503 TEMPORARY ERROR');
        return 0;
    }
}

return HTTPError503::class;
