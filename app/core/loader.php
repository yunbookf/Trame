<?php

define('T_ROOT', './');
define('T_APP_ROOT', T_ROOT . 'app/');
define('T_CORE_ROOT', T_APP_ROOT . 'core/');
define('T_CONFIG_ROOT', T_ROOT . 'etc/');
define('T_TEMP_ROOT', T_ROOT . 'temp/');
define('T_LOGS_ROOT', T_ROOT . 'logs/');
define('T_DATE_ROOT', T_ROOT . 'data/');
define('T_STATIC_ROOT', T_ROOT . 'public/');
define('T_MSG_ROOT', T_APP_ROOT . 'msgs/');
define('T_SERVICE_ROOT', T_APP_ROOT . 'service/');
define('T_VIEW_ROOT', T_APP_ROOT . 'views/');

require T_CONFIG_ROOT . 'links.php';
require T_CONFIG_ROOT . 'version.php';
require T_CONFIG_ROOT . 'router.php';
require T_CORE_ROOT . 'langext.php';
require T_CORE_ROOT . 'action.php';
require T_CORE_ROOT . 'msg.php';
require T_CORE_ROOT . 'service.php';
require T_CORE_ROOT . 'http.php';

define('TIMENOW', $_SERVER['REQUEST_TIME']);
define('DATENOW', date('Y-m-d H:i:s', TIMENOW));

spl_autoload_register(function(string $class) {

    if (substr($class, 0, 6) === 'T\\Msg\\') {

        require T_MSG_ROOT . substr($class, 6) . '.php';
    }

    if (substr($class, 0, 10) === 'T\\Service\\') {

        require T_SERVICE_ROOT . substr($class, 10) . '.php';
    }
});