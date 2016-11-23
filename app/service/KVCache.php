<?php

declare (strict_types = 1);

namespace T\Service;

require T_CONFIG_ROOT . 'cache.php';

class KVCache extends IService {

    /**
     * The pool of connections
     *
     * @var array
     */
    protected static $__connPool = [];

    /**
     * This method provides the way to get a connection by external
     * configuration of cache.
     *
     * @param array $config
     *            the configuration of connection
     * @param string $id
     *            the id of connection. if NULL passed,
     *            used an auto_incremental numeric index
     *            as unnamed id.
     * @return \T\KVCache\IConnection
     * @throws \T\Msg\ServiceFailure
     */
    public static function connect(array $config, string $id) {

        $driver = '\\T\\KVCache\\' . $config['engine'];

        if (!class_exists($driver)) {

            require T_SERVICE_ROOT . 'Cache/' . $config['engine'] . '.php';
        }

        if (!class_exists($driver)) {

            throw new \T\Msg\ServiceFailure(
                'KVCache: Unknown driver was found - ' . $config['engine']
            );
        }

        try {

            $conn = new $driver($config);
            self::$__connPool[$id] = $conn;
            return $conn;
        }
        catch (\PDOException $e) {

            throw new \T\Msg\ServiceFailure(
                'KVCache: Failed to connect to cache ' . $id . '. Reason: ' .
                $e->getMessage()
            );
        }

    }

    /**
     * This method tries picking a connection by id.
     * If connection of this id is not connected but existing in
     * /etc/KVCache.php, it will auto connect and return the connection
     * object.
     *
     * @param string $id
     *            the id of connection.
     * @return \T\KVCache\IConnection
     * @throws \T\Msg\ServiceFailure
     */
    public static function get(string $id) {

        if (self::check($id)) {

            return self::$__connPool[$id];
        }

        if (empty(\T\Config\CACHE[$id])) {

            throw new \T\Msg\ServiceFailure(
                'KVCache: Unknown id was found - ' . $id
            );
        }

        return self::connect(\T\Config\CACHE[$id], $id);

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

namespace T\KVCache;

abstract class IConnection {

    abstract public function __construct(array $config);

    /**
     * Read a key from cache.
     * 
     * @param string $key
     * @return any
     *     Return null if failed.
     */
    abstract public function get(string $key);

    /**
     * Read all values whose keys matched by a Rule from cache.
     * 
     * @param string $keyRules   Rule of key
     * @return array
     */
    abstract public function getEx(string $keyRules): array;

    /**
     * Delete a key from cache.
     * 
     * @param string $key
     * @return bool
     */
    abstract public function del(string $key): bool;

    /**
     * Delete all keys matched by a Rule from cache.
     * 
     * @param string $keyRules   Rule of key
     * @return int
     *     Return the number of keys deleted.
     */
    abstract public function delEx(string $keyRules): int;

    /**
     * Delete multi values from cache. 
     *
     * @param array $keys
     * @param array &$results
     *     optional, to receive failed keys.
     *
     * @return int Returns the number of succeed operations.
     */
    abstract public function multiDel(array $keys, array &$results = null): int;

    /**
     * Check if a key exists in the cache.
     * 
     * @param string $key
     * @return bool
     */
    abstract public function exist(string $key): bool;

    /**
     * Read multi keys from cache.
     * 
     * @param array<string> $keys
     * @return array
     */
    abstract public function multiGet(array $keys): array;

    /**
     * Write a value into cache. It will overwrite the old value if the key
     * already exists.
     * 
     * @param string $keys
     * @param any $value
     * @param int $expires
     */
    abstract public function set(string $keys, $value, int $expires = 0): bool;

    /**
     * Write multi values into cache. It will overwrite the old value if the key
     * already exists.
     * 
     * @param array $kvParis
     * @param int $expires
     * 
     * @return string[] Returns the keys list failed.
     */
    abstract public function multiSet(array $kvParis, int $expires = 0, array &$results = null): int;

    /**
     * Add multi values into cache. It will fail if a key already exists.
     * 
     * @param array $kvParis
     * @param int $expires
     * @param array &$results
     *     optional, to receive failed keys.
     * 
     * @return int Returns the number of succeed operations.
     */
    abstract public function multiAdd(array $kvParis, int $expires = 0, array &$results = null): int;

    /**
     * Add a value into cache。 It will failed if the key already exists.
     * 
     * @param string $keys
     * @param any $value
     * @param int $expires
     */
    abstract public function add(string $keys, $value, int $expires = 0): bool;

    /**
     * Count how many keys matched rules.
     * 
     * @param string $keyRules the rule for searching keys.
     * @return int
     */
    abstract public function count(string $keyRules = null): int;

    /**
     * Get list of keys matched rules.
     * 
     * @param string $keyRules
     *     the rule for searching keys.
     *
     * @return string[]
     */
    abstract public function keys(string $keyRules = null): array;

    /**
     * Removes all data in cache.
     * 
     * @return bool
     */
    abstract public function flush(): bool;

    /**
     * Replace value in cache with a new value, if the current value matches
     * the specific value.
     * 
     * @param string $keys
     * @param any $newValue
     * @param any $oldValue
     * 
     * @return bool
     */
    // abstract public function cas(string $keys, $newValue, $oldValue): bool;

}
