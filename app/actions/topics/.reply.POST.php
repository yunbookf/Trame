<?php
namespace T\Action;

/**
 * URI: POST /topics/{#id}/reply
 */

class ReadTopic {

    public function main(array $args) {

        echo 'You are reading topic of ID ', $args['id'], '.';
    }
}

return ReadTopic::class;
