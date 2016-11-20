<?php

namespace T;

require 'app/core/boot.php';

$__BOOTER = function() {

    global $__BOOTER;

    unset($__BOOTER);

    $router = require ('app/core/router.d/apcu.php');

    $actionInfo = $router($_GET['__uri'], $_SERVER['REQUEST_METHOD']);

    $className = require ($actionInfo['path']);

    $action = new $className();

    $action->main($actionInfo['args']);
};

try {

    $__BOOTER();

} catch (core\IMessage $e) {

    $e->handle();

} catch (\Exception $e) {

    \T\Service\Logger::writeLine('sql.error', $e->__toString());
}
