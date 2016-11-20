<?php
namespace T\Action;

/**
 * URI: GET /forums
 */

class ForumList {

    public function main(array $args) {
        echo 'forum list';
    }
}

return ForumList::class;
