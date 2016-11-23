<?php

declare (strict_types = 1);

namespace T\Service;

require T_CONFIG_ROOT . 'database.php';

class Database extends IService {

    /**
     * The pool of connections
     *
     * @var array
     */
    protected static $__connPool = [];

    /**
     * This method provides the way to get a connection by external
     * configuration of database.
     *
     * @param array $config
     *            the configuration of connection
     * @param string $id
     *            the id of connection. if NULL passed,
     *            used an auto_incremental numeric index
     *            as unnamed id.
     * @return \T\TDBI\IDBConnection
     * @throws \T\Msg\ServiceFailure
     */
    public static function connect(array $config, string $id) {

        $driver = '\\T\\TDBI\\' . $config['engine'];

        if (!class_exists($driver)) {

            require T_SERVICE_ROOT . 'Database/' . $config['engine'] . '.php';
        }

        if (!class_exists($driver)) {

            throw new \T\Msg\ServiceFailure(
                'Database: Unknown driver was found - ' . $config['engine']
            );
        }

        try {

            $conn = new $driver($config);
            self::$__connPool[$id] = $conn;
            return $conn;
        }
        catch (\PDOException $e) {

            throw new \T\Msg\ServiceFailure(
                'Database: Failed to connect to database ' . $id . '. Reason: ' .
                $e->getMessage()
            );
        }

    }

    /**
     * This method tries picking a connection by id.
     * If connection of this id is not connected but existing in
     * /etc/TDBI.php, it will auto connect and return the connection
     * object.
     *
     * @param string $id
     *            the id of connection.
     * @return \T\TDBI\IDBConnection
     * @throws \T\Msg\ServiceFailure
     */
    public static function get(string $id) {

        if (self::check($id)) {

            return self::$__connPool[$id];
        }

        if (empty(\T\Config\DATABASE[$id])) {

            throw new \T\Msg\ServiceFailure(
                'Database: Unknown id was found - ' . $id
            );
        }

        return self::connect(\T\Config\DATABASE[$id], $id);

    }

    /**
     * This method returns whether a connection is built.
     *
     * @param string $id
     *            the id of connection.
     * @return bool returns true if yes, or false.
     */
    public static function check(string $id): bool {

        return isset(self::$__connPool[$id]);

    }

    /**
     * This method will try killing a connection in the pool.
     *
     * @param string $id
     *            the id of connection.
     */
    public static function shutdown(string $id) {

        if (self::check($id)) {

            self::$__connPool[$id] = null;
            unset(self::$__connPool[$id]);
        }

    }

}

namespace T\TDBI;

abstract class IDBConnection extends \PDO {

    /**
     * How many rows were affected in last query.
     * @var int
     */
    public $affectedRows = 0;

    public function getError() {

        $err = $this->errorInfo();

        if (is_array($err) && isset($err[2])) {

            return [
                'pdo-error-code' => $err[0],
                'code' => $err[1],
                'message' => $err[2]
            ];
        }

        return null;

    }

    /**
     * Execute a Modification SQL.
     * All failure will be recorded in file /logs/sql.failure.log
     * 
     * @throws \T\Msg\SQLFailure
     * @return int
     */
    public function exec($sql): int {

        $result = parent::exec($sql);

        if ($result === false) {

            $err = $this->errorInfo();
            $callee = getCallerLine();
            throw new \T\Msg\SQLFailure(<<<SQL
SQL: {$sql}
Error Code: {$err[1]}
Error Detail: {$err[2]}
Source Position: {$callee}
SQL
            );
        }

        return $this->affectedRows = $result;
    }

    /**
     * Execute a Query SQL.
     * All failure will be recorded in file /logs/sql.failure.log
     * 
     * @throws \T\Msg\SQLFailure
     * @return \PDOStatement
     */
    public function query(string $sql): \PDOStatement {

        $result = parent::query($sql);

        if ($result === false) {

            $err = $this->errorInfo();
            $callee = getCallerLine();
            throw new \T\Msg\SQLFailure(<<<SQL
SQL: {$sql}
Error Code: {$err[1]}
Error Detail: {$err[2]}
Source Position: {$callee}
SQL
            );
        }

        return $result;
    }
}
