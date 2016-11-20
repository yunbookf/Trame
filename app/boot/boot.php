<?php

namespace T;

require 'app/core/loader.php';

$__BOOTER = function() {

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

    $action->main($actionInfo['args']);
};

try {

    $__BOOTER();

} catch (core\IMessage $e) {

    $e->handle();

} catch (\PDOException $e) {

    \T\Service\Logger::write('sql.error', $e->__toString());

} catch (\Exception $e) {

    \T\Service\Logger::write('bugs', $e->__toString());
}
