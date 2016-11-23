<?php

namespace T\Action;

use \T\Service as service, \T\Msg as msg;

/**
 * URI: /*
 */
class DevTest extends IAction {

    public function main(array $args): int {

        $this->cache->multiSet([
            '/dev/sda' => 'hello',
            '/dev/sdb' => 'world',
            '/dev/sdc' => '233333'
        ]);

        var_dump($this->cache->count('/dev/*'));
        echo '<br>';
        var_dump($this->cache->keys('/dev/*'));
        echo '<br>';
        var_dump($this->cache->getEx('/dev/*'));
        echo '<br>';
        var_dump($this->cache->delEx('/dev/*'));
        echo '<br>';
        var_dump($this->cache->getEx('/dev/*'));
        echo '<br>';
        return 0;
    }
}

return DevTest::class;
