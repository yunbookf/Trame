<?php
declare(strict_types = 1);

namespace T;

return function (string $uri, string $method): array {

    $srt = require ('app/boot/static-router-table.php');

    if (isset($uri[1]) && substr($uri, -1) === "/") {

        $uri = substr($uri, 0, -1);
    }

    $args = [];

    $path = $srt['ALL'][$uri] ?? ($srt[$method][$uri] ?? null);

    if ($path === null && \T\Config\ROUTER['dynamic-disabled'] === false) {

        $drt = require ('app/boot/dynamic-router-table.php');

        foreach ([
            'ALL',
            $method
        ] as $m) {

            foreach ($drt[$m] as $rule) {

                if (preg_match($rule['expr'], $uri, $matches)) {

                    $path = $rule['path'];

                    foreach ($rule['vars'] as $index => $varName) {

                        $args[$varName] = $matches[$index + 1];
                    }
                }
            }
        }
    }

    if ($path === null) {

        throw new Msg\RouteFailure('', \T\HTTP\NOT_FOUND);
    }

    return [
        'path' => $path,
        'args' => $args
    ];
};
