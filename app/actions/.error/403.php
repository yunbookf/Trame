<?php
namespace T\Action;

/**
 * URI: /
 */

class HTTPError403 extends IAction {

    public function main(array $args): int {
        header('HTTP/1.1 403 FORBIDDEN');
        return 0;
    }
}

return HTTPError403::class;
