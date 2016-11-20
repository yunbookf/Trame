<?php
return [
    'ALL' => [

    ],
    'GET' => [
        [
            'expr' => '/^\\/topics\\/(\\d+)$/',
            'path' => 'app/actions/topics/.topic.php',
            'vars' => ["id"]
        ]
    ],
    'POST' => [
        [
            'expr' => '/^\\/topics\\/(\\d+)\\/replies$/',
            'path' => 'app/actions/topics/.reply.POST.php',
            'vars' => ["id"]
        ]
    ],
    'PUT' => [

    ],
    'DELETE' => [

    ]
];
