<?php
declare (strict_types = 1);

namespace T\Core;

abstract class IMessage extends \Exception {

    abstract public function handle();
}
