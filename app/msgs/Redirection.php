<?php
declare(strict_types = 1);

namespace T\Msg;

use T\Core\IMessage;

class Redirection extends IMessage {

    public function handle() {

        header('location: ' . $this->getMessage());

    }
}
