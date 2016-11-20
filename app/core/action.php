<?php
declare (strict_types = 1);

namespace T\Action;

abstract class IAction {

    abstract public function main(array $args): int;
}
