<?php
declare (strict_types = 1);

namespace T\Model;

use \T\Msg as msg;

/**
 * 该类是所有 Model 工厂的抽象基类，也是所有 Model 工厂的管理器。
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

    /**
     * 根据主键获取一个 Model
     * 
     * @param array $conds      可选，指定筛选的条件
     */
    abstract public function get($any);
}

/**
 * @property \T\TDBI\IDBConnection $db
 *     默认的数据库链接对象（首次调用时分配）
 * @property \T\KVCache\IConnection $cache
 *     默认的缓存链接对象（首次调用时分配）
 */
abstract class IModel {

    use \T\Core\TDelayInitializer;

    protected $__values;

    public function __construct(array $data = []) {

        $this->di = [

            'db' => function() {

                return \T\Service\Database::get(\T\Links\DATABASE_DEFAULT);
            },

            'cache' => function() {

                return \T\Service\KVCache::get(\T\Links\CACHE_DEFAULT);
            }
        ];

        $this->__values = $data;
    }

    /**
     * 撤销当前对该 Model 做的修改（已经提交的不能撤销）
     * 
     * @param string $name
     * @return static
     */
    abstract public function reset(string $name = null);

    /**
     * 读取 Model 的一个属性
     * 
     * @param string $name
     * @throws msg\InvalidProperty
     * @return any
     */
    abstract public function get(string $name);

    /**
     * 修改一个属性的值，但要调用 save 之后才提交
     * @param string $name
     * @param any $val
     * @return static
     */
    abstract public function set(string $name, $val);

    /**
     * 检测一个属性是否已经被当前会话修改过，且未提交。
     * @param string $name
     * @return bool
     */
    abstract public function isChanged(string $name = null): bool;

    /**
     * 提交对该 Model 做的修改。
     * @param string $name
     * @return bool
     */
    abstract public function save(): bool;
}

abstract class IORModelFactory extends ModelFactory {

    public function getTableName(): string {

        return static::MODEL_TABLE;
    }

    /**
     * 根据主键获取一个 Model
     *
     * @param array $conds      可选，指定筛选的条件
     * @return \T\Model\IModel
     */
    public function get($params) {

        static $sql;

        if (!$sql) {

            $sql = $this->db->prepare($this->db->sql('/sql/query/pkey/' . static::class, function($sql) {

                $conds = [];
                if (is_array(static::MODEL_PRIMARY_KEY)) {

                    foreach (static::MODEL_PRIMARY_KEY as $k) {

                        $conds['%' . static::__FILED_MAP_TABLE[$k]] = $k;
                    }
                }
                else {

                    $k = static::MODEL_PRIMARY_KEY;

                    $conds['%' . static::__FILED_MAP_TABLE[$k]] = $k;
                }

                $sql->select(array_combine(
                    array_values(static::__FILED_MAP_TABLE),
                    array_keys(static::__FILED_MAP_TABLE)
                ))->from(static::MODEL_TABLE)->where($conds)->limit(1)->end();
            }));
        }

        $conds = [];

        if (is_array(static::MODEL_PRIMARY_KEY)) {
        
            foreach (static::MODEL_PRIMARY_KEY as $k) {
        
                $conds[':' . static::__FILED_MAP_TABLE[$k]] = $params[$k];
            }
        }
        else {
        
            $k = static::MODEL_PRIMARY_KEY;
        
            $conds[':' . static::__FILED_MAP_TABLE[$k]] = $params;
        }

        if ($sql->execute($conds)) {

            if ($r = $sql->fetch(\PDO::FETCH_ASSOC)) {

                $sql->closeCursor();
                $class = static::MODEL_CLASS;
                return new $class($r);
            }

            $sql->closeCursor();
        }

        return null;
    }

    /**
     * 根据条件从数据库筛选符合条件的 Model
     * 
     * @param array $conds      可选，指定筛选的条件
     * @param int   $limit      可选，指定要获取的条目数
     * @param int   $offset     可选，指定获取条目的偏移
     * @param array $orders     可选，指定结果的排序方式
     * @return array
     */
    public function find(
        array $conds = null,
        int $limit = null,
        int $offset = 0,
        array $orders = []
    ): array {

        return [];
    }

    /**
     * 根据条件从数据库筛选符合条件的 Model
     * 
     * @param array $conds      可选，指定筛选的条件
     * @param int   $limit      可选，指定要获取的条目数
     * @param int   $offset     可选，指定获取条目的偏移
     * @param array $orders     可选，指定结果的排序方式
     * @return array
     */
    public function count(
        array $conds = null
    ): int {
        return 0;
    }
}

abstract class IORModel extends IModel {

    const __CHANGE_ACTION_SET   = 0;

    const __CHANGE_ACTION       = 0;
    const __CHANGE_ORIGINAL     = 1;
    const __CHANGE_FIELD        = 2;

    /**
     * @var array
     */
    protected $__changes = [];

    public function set(string $name, $val) {

        if (empty(static::__FILED_MAP_TABLE[$name])) {

            throw new msg\InvalidProperty($name);
        }

        if (isset($this->__changes[$name]) && $this->__changes[$name][self::__CHANGE_ORIGINAL] == $val) {

            unset($this->__changes[$name]);
        }
        else {

            $this->__changes[$name] = [
                self::__CHANGE_ACTION_SET,
                $this->__values[$name],
                static::__FILED_MAP_TABLE[$name]
            ];
        }

        $this->__values[$name] = $val;

        return $this;
    }

    public function reset(string $name = null) {

        if ($name) {

            if (isset($this->__changes[$name])) {

                $this->__values[$name] = $val[self::__CHANGE_ORIGINAL];

                unset($this->__changes[$name]);
            }
        }
        else {
            
            foreach ($this->__changes as $field => $val) {

                $this->__values[$field] = $val[self::__CHANGE_ORIGINAL];
            }

            $this->__changes = [];
        }

        return $this;
    }

    public function __get(string $name) {

        if (empty(static::__FILED_MAP_TABLE[$name])) {

            throw new msg\InvalidProperty($name);
        }

        return $this->__values[$name];
    }

    public function get(string $name) {

        if (empty(static::__FILED_MAP_TABLE[$name])) {

            throw new msg\InvalidProperty($name);
        }

        return $this->__values[$name];
    }

    public function isChanged(string $name = null): bool {

        if ($name) {

            return isset($this->__changes[$name]);
        }

        return $this->__changes ? true : false;
    }

    public function save(): bool {

        return false;
    }
}
