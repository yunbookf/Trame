<?php
declare (strict_types = 1);

namespace T\Model;

/**
 * This class provides the based-class of all Model-Factory classes,
 * and work in static way as a factory of all Model-Factory classes.
 * @property \T\TDBI\IDBConnection $db
 *     默认的数据库链接对象（首次调用时分配）
 * @property \T\KVCache\IConnection $cache
 *     默认的缓存链接对象（首次调用时分配）
 */
abstract class ModelFactory {

    use \T\Core\TDelayInitializer;

    protected function __construct() {

        $this->di = [
            'db' => function() {
                return \T\Service\Database::get(\T\Links\DATABASE_DEFAULT);
            },
            'cache' => function() {
                return \T\Service\KVCache::get(\T\Links\CACHE_DEFAULT);
            }
        ];
    }

    protected static $factorys = [];

    public static function getFactory(string $name): ModelFactory {

        if (empty(self::$factorys[$name])) {

            require T_MODEL_ROOT . $name . '.php';
            $className = '\\T\\Model\\' . $name . 'Factory';
            self::$factorys[$name] = new $className();
        }

        return self::$factorys[$name];
    }

    public function getTableName(): string {

        return static::MODEL_TABLE;
    }

    abstract public function get($args);
}

/**
 * @property \T\TDBI\IDBConnection $db
 *     默认的数据库链接对象（首次调用时分配）
 * @property \T\KVCache\IConnection $cache
 *     默认的缓存链接对象（首次调用时分配）
 */
abstract class IModel {

    use \T\Core\TDelayInitializer;

    public function __construct() {

        $this->di = [
            'db' => function() {
                return \T\Service\Database::get(\T\Links\DATABASE_DEFAULT);
            },
            'cache' => function() {
                return \T\Service\KVCache::get(\T\Links\CACHE_DEFAULT);
            }
        ];
    }

    abstract public function abc();
}
