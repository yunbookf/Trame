<?php
declare(strict_types = 1);

return function (string $uri, string $method): array {
    
    if (isset($uri[1]) && substr($uri, -1) === "/") {
        
        $uri = substr($uri, 0, -1);
    }
    
    if (!apcu_exists('/trame/router/inited')) {
        
        $srt = require ('app/boot/static-router-table.php');
        
        foreach ($srt as $m => $table) {
            
            foreach ($table as $key => $v) {
                
                echo '/trame/s-router/' . $m . $key, '<br>';
                apcu_store('/trame/s-router/' . $m . $key, $v);
            }
        }
        
        $drt = require ('app/boot/dynamic-router-table.php');
        
        foreach ($drt as $m => $table) {
            
            apcu_store('/trame/d-router/' . $m, $table);
        }
        
        apcu_add('/trame/router/inited', 1);
    }
    
    $args = [];
    
    $path = apcu_fetch('/trame/s-router/ALL' . $uri, $result);
    
    if ($result === false) {
        
        $path = apcu_fetch('/trame/s-router/' . $method . $uri, $result);
    }
    
    if ($result === false && \T\Config\ROUTER['dynamic-disabled'] === false) {
        
        $path = null;
        
        foreach ([
            'ALL',
            $method
        ] as $m) {

            $drt = apcu_fetch('/trame/d-router/' . $m, $result);
            
            if ($result) {
                
                foreach ($drt as $rule) {
                    
                    if (preg_match($rule['expr'], $uri, $matches)) {
                        
                        $path = $rule['path'];
                        
                        foreach ($rule['vars'] as $index => $varName) {
                            
                            $args[$varName] = $matches[$index + 1];
                        }
                    }
                }
            }
        }
    }
    
    return [
        'path' => $path === null ? 'app/actions/.error/404.php' : $path,
        'args' => $args
    ];
};
