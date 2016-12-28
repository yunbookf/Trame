<?php
/**
 * 本文件提供 Key-Value 缓存的统一管理器和统一接口 KVCache。
 * 
 * @author Angus Fenying <i.am.x.fenying@gmail>
 */
declare (strict_types = 1);

namespace T\Service;

require T_CONFIG_ROOT . 'cache.php';

class KVCache extends IService {

    /**
     * 缓存链接池
     *
     * @var array
     */
    protected static $connPool = [];

    /**
     * 这个方法通过配置连接缓存，并返回一个缓存链接。
     *
     * @param array $config
     *     缓存链接配置
     * @param string $id
     *     对应的链接命名ID
     *
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
            self::$connPool[$id] = $conn;
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
     * 本方法尝试按命名 ID在 /etc/cache.php 中寻找对应的缓存配置，
     * 并根据配置连接缓存，返回链接对象。
     *
     * @param string $id
     *     链接的命名 ID
     *
     * @return \T\KVCache\IConnection
     * @throws \T\Msg\ServiceFailure
     */
    public static function get(string $id) {

        if (self::check($id)) {

            return self::$connPool[$id];
        }

        if (empty(\T\Config\CACHE[$id])) {

            throw new \T\Msg\ServiceFailure(
                'KVCache: Unknown id was found - ' . $id
            );
        }

        return self::connect(\T\Config\CACHE[$id], $id);

    }

    /**
     * 这个方法用于检测一个命名ID对应的链接是否已经存在。
     *
     * @param string $id
     *     链接的命名ID
     * @return bool
     */
    public static function check(string $id): bool {

        return isset(self::$connPool[$id]);

    }

    /**
     * 本方法将从链接池释放一个缓存链接。
     * 注意：这并不意味着缓存链接将被断开，因为可能在其它位置也有引用该缓存链接对象。
     *
     * @param string $id
     *     链接的命名ID
     */
    public static function shutdown(string $id) {

        if (self::check($id)) {

            self::$connPool[$id] = null;
            unset(self::$connPool[$id]);
        }

    }

}

namespace T\KVCache;

interface IConnection {

    public function __construct(array $config);

    /**
     * 根据 key 从缓存中读取一个值。
     * 
     * @param string $key
     * @return any
     *     不存在时返回 null
     */
    public function get(string $key);

    /**
     * 根据 key 的泛匹配式搜索并返回对应的键值。
     * 
     * Tips: $keyRules => '*' 等价于 $keyRules => null，都是匹配所有 key。
     * 
     * @param string $keyRules
     *     key 的泛匹配式
     * 
     * @return array
     *     返回检索到的 key->value 式数组，没找到任何符合的 key 时返回空数组。
     */
    public function getEx(string $keyRules): array;

    /**
     * 根据 key 数组从缓存中一次读取多对键值。
     * 
     * @param array<string> $keys
     * @return array
     *     返回检索到的 key->value 式数组，未找到的 key 对应值为 null。
     */
    public function multiGet(array $keys): array;

    /**
     * 根据 key 从缓存中删除一个键值。
     * 
     * @param string $key
     * @return bool
     */
    public function del(string $key): bool;

    /**
     * 根据 key 的泛匹配式搜索并删除对应的键值。
     * 
     * Tips: $keyRules => '*' 等价于 $keyRules => null，都是匹配所有 key。
     * 
     * @param string $keyRules
     *     key 的泛匹配式
     * 
     * @return int
     *     返回删除的键值数量。
     */
    public function delEx(string $keyRules): int;

    /**
     * 根据 key 数组一次删除多对键值。
     *
     * @param array $keys
     *     key 数组
     * @param array &$results
     *     可选参数，用于接收删除失败的key集合
     *
     * @return int
     *     本方法返回成功删除的键值数量。
     */
    public function multiDel(array $keys): int;

    /**
     * 检查一个键值是否存在
     * 
     * @param string $key
     * @return bool
     */
    public function exist(string $key): bool;

    /**
     * 向缓存中写入一对键值，如果指定的 key 已经存在，则对应的值会被覆盖掉。
     * 
     * @param string $key
     * @param any $value
     * @param int $expires
     *     有效期，单位为秒。0 为默认值，表示永不过期。
     *
     * @return bool
     *     成功返回 true
     */
    public function set(string $key, $value, int $expires = 0): bool;

    /**
     * 向缓存一次写入多对键值。如果指定的 key 已经存在，则对应的值会被覆盖掉。
     * 
     * @param array $kvParis
     *     键值对数组
     * @param int $expires
     *     有效期，单位为秒。0 为默认值，表示永不过期。
     * @param array &$results
     *     可选参数，用于接收写入失败的key集合
     * 
     * @return int
     *     返回成功写入的键值对数量。
     */
    public function multiSet(array $kvParis, int $expires = 0): int;

    /**
     * 使一个键值自增。
     * 
     * @param string $key
     * @param int $step     自增的步长，默认为 1
     * @return int
     *     返回自增后的步长。
     */
    public function increase(string $key, int $step = 1): int;

    /**
     * 向缓存中写入一对键值，如果指定的 key 已经存在，则写入失败。
     * 
     * @param string $key
     * @param any $value
     * @param int $expires
     *     有效期，单位为秒。0 为默认值，表示永不过期。
     *
     * @return bool
     *     成功返回 true
     */
    public function add(string $keys, $value, int $expires = 0): bool;

    /**
     * 统计缓存中有多少键值对，如果给第一个参数传递一个泛匹配式，则搜索并返回匹配的键值对数量。
     * 
     * Tips: $keyRules => '*' 等价于 $keyRules => null，都是匹配所有 key。
     * 
     * @param string $keyRules
     *     可选参数，key的泛匹配式
     *
     * @return int
     * 
     */
    public function count(string $keyRules = null): int;

    /**
     * 获取缓存中的key数组，如果给第一个参数传递一个泛匹配式，则搜索并匹配对应的 key。
     * 
     * Tips: $keyRules => '*' 等价于 $keyRules => null，都是匹配所有 key。
     * 
     * @param string $keyRules
     *     可选参数，key的泛匹配式
     *
     * @return string[]
     */
    public function keys(string $keyRules = null): array;

    /**
     * 清空缓存。
     * 
     * @return bool
     */
    public function flush(): bool;

    public function ping(): bool;
}
