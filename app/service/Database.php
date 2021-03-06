<?php
/**
 * 本文件提供关系型数据库的统一管理器和统一接口 Database。
 *
 * @author Angus Fenying <i.am.x.fenying@gmail>
 */
declare(strict_types = 1);

namespace T\Service;

use \T\Msg as msg, \T\Config as cfg;

require T_CONFIG_ROOT . 'database.php';

require T_SERVICE_ROOT . 'Database/SQLBuilder.php';

class Database extends IService {

    /**
     * 链接池
     *
     * @var array
     */
    protected static $connPool = [];

    /**
     * 这个方法通过配置连接数据库服务器，并返回一个数据库链接。
     *
     * @param array $config 数据库链接配置
     * @param string $id 对应的链接命名ID
     *
     * @return \T\TDBI\IDBConnection
     * @throws msg\ServiceFailure
     */
    public static function connect(array $config, string $id) {

        $driver = '\\T\\TDBI\\' . $config['engine'];

        if (!class_exists($driver)) {

            require T_SERVICE_ROOT . 'Database/' . $config['engine'] . '.php';
        }

        if (!class_exists($driver)) {

            throw new msg\ServiceFailure(
                'Database: Unknown driver was found - ' . $config['engine']);
        }

        try {

            $conn = new $driver($config);
            self::$connPool[$id] = $conn;
            return $conn;
        }
        catch (\PDOException $e) {

            throw new msg\ServiceFailure(
                'Database: Failed to connect to database ' . $id . '. Reason: ' .
                     $e->getMessage());
        }

    }

    /**
     * 本方法尝试按命名 ID在 /etc/database.php 中寻找对应的数据库配置，
     * 并根据配置连接数据库，返回链接对象。
     *
     * @param string $id 链接的命名 ID
     *
     * @return \T\TDBI\IDBConnection
     * @throws msg\ServiceFailure
     */
    public static function get(string $id) {

        if (self::check($id)) {

            return self::$connPool[$id];
        }

        if (empty(cfg\DB_SERVERS[$id])) {

            throw new msg\ServiceFailure(
                'Database: Unknown id was found - ' . $id);
        }

        return self::connect(cfg\DB_SERVERS[$id], $id);

    }

    /**
     * 这个方法用于检测一个命名ID对应的链接是否已经存在。
     *
     * @param string $id 链接的命名ID
     * @return bool
     */
    public static function check(string $id): bool {

        return isset(self::$connPool[$id]);

    }

    /**
     * 本方法将从链接池释放一个数据库链接。
     * 注意：这并不意味着数据库链接将被断开，因为可能在其它位置也有引用该数据库链接对象。
     *
     * @param string $id 链接的命名ID
     */
    public static function shutdown(string $id) {

        if (self::check($id)) {

            self::$connPool[$id] = null;
            unset(self::$connPool[$id]);
        }

    }

}

namespace T\TDBI;

use \T\Msg as msg, \T\Config as cfg;

abstract class IDBConnection extends \PDO {

    /**
     * 上次 exec 方法影响的行数
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
     * 执行一条 SQL 更新语句，该方法不适用于 SELECT 等具有返回结果集的查询。
     *
     * 本方法执行成功时，返回SQL语句影响的行数。执行失败则抛出异常，如果
     * 该异常未被捕获，则会被记录到 /logs/sql.failure.log。
     *
     * @throws msg\SQLFailure
     * @return int
     */
    public function exec($sql): int {

        $result = parent::exec($sql);

        if ($result === false) {

            $err = $this->errorInfo();
            $callee = getCallerLine();
            $this->affectedRows = 0;
            throw new msg\SQLFailure(
                <<<SQL
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
     * 执行一条 SQL 查询语句，该方法仅适用于 SELECT 等具有返回结果集的查询。
     *
     * 本方法执行成功时，返回结果集操作对象。执行失败则抛出异常，如果该异常未被
     * 捕获，则会被记录到 /logs/sql.failure.log。
     *
     * @throws msg\SQLFailure
     *
     * @return \PDOStatement
     */
    public function query(string $sql) {

        $result = parent::query($sql);

        if ($result === false) {

            $err = $this->errorInfo();
            $callee = getCallerLine();
            throw new msg\SQLFailure(
                <<<SQL
SQL: {$sql}
Error Code: {$err[1]}
Error Detail: {$err[2]}
Source Position: {$callee}
SQL
);
        }

        return $result;

    }

    /**
     * 
     * @param string $id
     * @param callable $fn
     * @return \T\TDBI\ISQLBuilder | \T\TDBI\SQLMultiInsert | string
     */
    public function sql($id = null, callable $fn = null) {

        static $cache;

        if ($id) {

            if (!$cache) {

                $cache = \T\Service\KVCache::get(\T\Links\CACHE_SQL);
            }

            if ($sql = $cache->get($id)) {

                return $sql;
            }
        }

        $sql = new SQLBuilder();

        if ($fn) {

            $fn($sql);

            if ($id) {

                $cache->set($id, $sql->getSQL(), cfg\DB_SETTINGS['sql-cache-expires']);
            }

            return $sql->getSQL();
        }

        return $sql;

    }

}
