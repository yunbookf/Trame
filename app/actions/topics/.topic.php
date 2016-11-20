<?php
namespace T\Action;

/**
 * URI: /topics/{#id}
 */

class ReadTopic {

    public function main(array $args) {

        echo 'You are reading topic of ID ', $args['id'], '.';
    }
}

return ReadTopic::class;
