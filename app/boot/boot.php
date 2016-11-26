<?php

namespace T;

require 'app/core/loader.php';

return function() {

    global $__BOOTER;

    unset($__BOOTER);

    $router = require ('app/core/router.d/default.php');

    try {

        $actionInfo = $router($_GET['__uri'], $_SERVER['REQUEST_METHOD']);

        require 'app/boot/rc.php';

    } catch (\T\Msg\RouteFailure $e) {

        $actionInfo = [
            'path' => 'app/actions/.error/' . $e->getCode() . '.php',
            'args' => []
        ];
    }

    $className = require ($actionInfo['path']);

    $action = new $className();
    $args = $actionInfo['args'];

    unset($className, $router, $actionInfo);
    $action($args);
};
