<?php
return [
    'ALL' => [
        '/' => 'app/actions/index.php',
        '/test' => 'app/actions/test.php',
        '/tpltest' => 'app/actions/tpltest.php'
    ],
    'GET' => [
        '/forums' => 'app/actions/forums/index.GET.php'
    ],
    'POST' => [
        '/forums' => 'app/actions/forums.DELETE.POST.php',
        '/topics/replies' => 'app/actions/topics/.test/fff.php'
    ],
    'DELETE' => [
        '/forums' => 'app/actions/forums.DELETE.POST.php'
    ]
];
