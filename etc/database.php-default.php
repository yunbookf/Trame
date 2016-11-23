<?php
namespace T\Config;

const DATABASE = [
    'mysql' => [
        'engine' => 'mysql',
// -- optional {
//      'unix_socket' => '',
        'host' => '127.0.0.1', # comment this line if unix_socket is used.
        'port' => 3306,
        'charset' => 'utf8',
        'dbname' => 'test',
// -- }
        'username' => 'root',
        'password' => '',
        'pdo-options' => [
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        ]
    ],
//    'sqlite' => [
//        'engine' => 'sqlite3',
//        'location' => 'e:/test.sql3', # supports :memory: as memory database
//        'pdo-options' => [
//            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
//        ]
//    ]
];
