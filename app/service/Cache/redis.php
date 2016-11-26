<?php
declare (strict_types = 1);

namespace T\KVCache;

use \T\Msg\CacheFailure;

class redis extends \Redis implements IConnection {

    /**
     * 
     * @var \Redis
     */
    protected $conn;

    protected $db;
    public function __construct(array $cfg) {

        if (!class_exists('\\Redis')) {

            throw new CacheFailure(
                'KVCache: Redis extension is not installed.'
            );
        }
        $this->conn = new \Redis();

        if ($cfg['pconnect']) {

            if (!$this->conn->pconnect($cfg['host'], $cfg['port'])) {

                throw new CacheFailure(
                    'KVCache: Failed to connect to Redis server.'
                );
            }

        } elseif (!$this->conn->connect($cfg['host'], $cfg['port'])) {

            throw new CacheFailure(
                'KVCache: Failed to connect to Redis server.'
            );
        }

        if (isset($cfg['password']) && !$this->conn->auth($cfg['password'])) {

            $this->conn->close();

            throw new CacheFailure(
                'KVCache: Failed to authenticate with Redis server.'
            );
        }

        if (($this->db = $cfg['db']) && !$this->conn->select($cfg['db'])) {

            throw new CacheFailure(
                'KVCache: Specific redis database ID is invalid.'
            );
        }
    }

    public function add(string $key, $value, int $expires = 0): bool {

        if ($this->conn->setnx($key, serialize($value))) {

            if ($expires) {

                $this->conn->expire($key, $expires);

            }

            return true;
        }

        return false;
    }

    public function exist(string $key): bool {

        return $this->conn->exists($key);
    }

    public function set(string $key, $value, int $expires = 0): bool {

        if (!$expires) {

            return $this->conn->set($key, serialize($value));
        }

        return $this->conn->setex($key, $expires, serialize($value));
    }

    public function del(string $key): bool {

        return $this->conn->del($key) ? true : false;
    }

    public function delEx(string $rule): int {

        $keys = $this->conn->keys($rule);

        return $this->conn->del($keys);
    }

    public function get(string $key) {

        $value = $this->conn->get($key);

        return is_string($value) ? unserialize($value) : null;
    }

    public function getEx(string $keyRules): array {

        $keys = $this->conn->keys($keyRules);
        $data = $this->conn->mget($keys);

        $ret = [];

        foreach ($keys as $index => $key) {

            $ret[$key] = ($data[$index] !== false) ? unserialize($data[$index]) : null;
        }

        return $ret;
    }

    public function multiGet(array $keys): array {

        $data = $this->conn->mget($keys);
        
        $ret = [];
        
        foreach ($keys as $index => $key) {
        
            $ret[$key] = ($data[$index] !== false) ? unserialize($data[$index]) : null;
        }
        
        return $ret;
    }

    public function multiDel(array $keys): int {

        return $this->conn->del($keys);
    }

    public function multiSet(array $kvParis, int $expires = 0): int {

        $ret = 0;

        $pipeline = $this->conn->pipeline();

        if ($expires) {

            foreach ($kvParis as $key => $value) {

                $pipeline->setex($key, $expires, serialize($value));
            }

        } else {

            foreach ($kvParis as $key => $value) {

                $pipeline->set($key, serialize($value));
            }
        }

        $results = $pipeline->exec();

        if (!is_array($results)) {

            return 0;
        }

        foreach ($results as $result) {

            if ($results) {

                ++$ret;
            }
        }

        return $ret;
    }

    public function increase(string $key, int $step = 1): int {

        if ($step === 1) {

            $result = $this->conn->incr($key);

        } else {

            $result = $this->conn->incrBy($step);;
        }

        if (!is_int($result)) {

            throw new CacheFailure(
                "KVCache: Failed to increase key {$key}."
            );
        }

        return $result;
    }

    public function decrease(string $key, int $step = 1): int {

        if ($step === 1) {

            $result = $this->conn->decr($key);

        } else {

            $result = $this->conn->decrBy($step);;
        }

        if (!is_int($result)) {

            throw new CacheFailure(
                "KVCache: Failed to decrease key {$key}."
            );
        }

        return $result;
    }

    public function flush(): bool {

        @$this->conn->flushDB();
        return true;
    }

    public function keys(string $keyRules = null): array {

        return $this->conn->keys($keyRules);
    }

    public function count(string $keyRules = null): int {

        if ($keyRules === null || $keyRules === '*') {

            if ($info = $this->conn->info('keyspace')) {

                if (isset($info['db' . $this->db])) {

                    return explode('=', explode(',', $info['db' . $this->db])[0])[1] + 0;
                }
            }
        }

        return count($this->conn->keys($keyRules));
    }

    public function ping(): bool {

        return $this->conn->ping() === "+PONG";
    }
}
