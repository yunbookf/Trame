<?php
declare (strict_types = 1);

namespace T\Action;

abstract class IAction {

    /**
     * The request info from object.
     * @var \T\HTTP\Request
     */
    protected $request;

    public function __construct() {

        $this->request = new \T\HTTP\Request();
    }

    abstract public function main(array $args): int;
}
