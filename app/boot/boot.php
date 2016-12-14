<?php

namespace T;

require 'app/core/loader.php';

return function() {

    global $__BOOTER;

    ini_set('display_errors', 'no');
    unset($__BOOTER);

    $router = require ('app/core/router.d/default.php');

    try {

        $actionInfo = $router($_GET['__uri'], $_SERVER['REQUEST_METHOD']);

        require 'app/boot/rc.php';

    } catch (\T\Msg\RouteFailure $e) {

        $actionInfo = [
            'path' => 'app/actions/.error/http.php',
            'args' => [$e->getCode()]
        ];
    }

    $className = require ($actionInfo['path']);

    $action = new $className();
    $args = $actionInfo['args'];

    set_error_handler(function(int $errno, string $errstr, string $errfile, int $errline): bool {

        \T\Service\Logger::write(
            'error',
            \T\Service\Logger::FETAL_ERROR,
            <<<ERROR
Code: {$errno}
Detail: {$errstr}
Position: {$errfile}:{$errline}
ERROR
        );

        return true;

    }, E_ALL);

    unset($className, $router, $actionInfo);
    $action->__exec($args);
};
