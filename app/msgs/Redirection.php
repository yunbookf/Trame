<?php
declare(strict_types = 1);

namespace T\msg;

use T\Core\IMessage;

class Redirection extends IMessage {

    public function handle() {

        header('location: ' . $this->getMessage());
    
    }

}
