<?php
namespace T\Action;

/**
 * URI: /*
 */

class DevTest extends IAction {

    public function main(array $args): int {
        throw new \T\Msg\HTTPError(0, \T\HTTP\NOT_FOUND);
        return 0;
    }
}

return DevTest::class;
