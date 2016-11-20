<?php

namespace T;

require 'app/core/boot.php';

$__BOOTER = function() {

    global $__BOOTER;

    unset($__BOOTER);

    $router = require ('app/core/router.d/default.php');

    $actionInfo = $router($_GET['__uri'], $_SERVER['REQUEST_METHOD']);

    $className = require ($actionInfo['path']);

    $action = new $className();

    $action->main($actionInfo['args']);
};

$__BOOTER();
