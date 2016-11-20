<?php
declare (strict_types = 1);

return function(string $uri, string $method): array {

    $srt = require ('app/boot/static-router-table.php');

    if (isset($uri[1]) && substr($uri, -1) === "/") {

        $uri = substr($uri, 0, -1);
    }

    $args = [];

    $path = $srt['ALL'][$uri] ?? ($srt[$method][$uri] ?? null);

    if ($path === null) {

        $drt = require ('app/boot/dynamic-router-table.php');

        foreach ($drt[$method] as $rule) {

            if (preg_match($rule['expr'], $uri, $matches)) {

                $path = $rule['path'];

                foreach ($rule['vars'] as $index => $varName) {

                    $args[$varName] = $matches[$index + 1];
                }
            }
        }
    }

    return [
        'path' => $path === null ? 'app/actions/.error/404.php' : $path,
        'args' => $args
    ];
};
