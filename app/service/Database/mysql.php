<?php

namespace T\TDBI;

class mysql extends IDBConnection {

    public function __construct(array $config) {

        $dsn = 'mysql:';

        foreach ( [ 
            'host',
            'port',
            'dbname',
            'unix_socket',
            'charset' 
        ] as $field ) {

            if (isset ( $config[$field] )) {

                $dsn .= $field . '=' . $config[$field] . ';';
            }
        }

        parent::__construct ( $dsn, $config['username'], $config['password'], $config['pdo-options'] );

    }
}
