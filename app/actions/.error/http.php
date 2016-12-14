<?php
namespace T\Action;

/**
 * URI: /
 */

class HTTPError extends IAction {

    public function main(array $args): int {

        $this->response->writeHeader('STATUS', $args[0]);
        return 0;
    }
}

return HTTPError::class;
