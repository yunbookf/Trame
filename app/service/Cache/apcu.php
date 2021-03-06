<?php
declare (strict_types = 1);

namespace T\KVCache;

class apcu implements IConnection {

    public function __construct(array $cfg) {

        if (!function_exists('apcu_enabled') || !apcu_enabled()) {

            throw new \T\Msg\CacheFailure(
                'KVCache: APCu is not installed.'
            );
        }
    }

    public function add(string $key, $value, int $expires = 0): bool {

        return apcu_add($key, $value, $expires);
    }

    public function exist(string $key): bool {

        return apcu_exists($key);
    }

/*
    public function cas(string $key, $newValue, $oldValue): bool {

        return apcu_cas($key, $oldValue, $newValue);
    }
*/

    public function set(string $key, $value, int $expires = 0): bool {

        return apcu_store($key, $value, $expires);
    }

    public function del(string $key): bool {

        return apcu_delete($key);
    }

    protected static function compileRule(string $rule): string {
        return '/^' . str_replace('@##', '.*', preg_quote(str_replace('*', '@##', $rule), '/')) . '$/';
    }

    public function delEx(string $rule): int {

        if ($rule === '*') {
            $ret = $this->count();
            return $this->flush() ? $ret : 0;
        }

        $ret = 0;

        foreach (new \APCuIterator(self::compileRule($rule)) as $key => $v) {

            if (apcu_delete($key)) {

                ++$ret;
            }
        }

        return $ret;
    }

    public function get(string $key) {

        $value = apcu_fetch($key, $success);

        return $success ? $value : null;
    }

    public function getEx(string $keyRules): array {

        $ret = [];
        
        foreach (new \APCUIterator(self::compileRule($keyRules)) as $key => $item) {
        
            $ret[$key] = $item['value'];
        }
        
        return $ret;
    }

    public function multiGet(array $keys): array {

        $ret = apcu_fetch($keys, $result);

        if (!$result) {

            $ret = [];

            foreach ($keys as $key) {

                $ret[$key] = null;
            }

            return [];
        }

        foreach ($keys as $key) {

            if (empty($ret[$key])) {

                $ret[$key] = null;
            }
        }

        return $ret;
    }

    public function multiDel(array $keys): int {

        return count($keys) - count(apcu_delete($keys));
    }

    public function multiSet(array $kvParis, int $expires = 0): int {

        return count($kvParis) - count(apcu_store($kvParis, null, $expires));
    }

    public function flush(): bool {

        return apcu_clear_cache();
    }

    public function increase(string $key, int $step = 1): int {
    
        $result = apcu_inc($key, $step, $success);

        if (!$success) {
        
            throw new CacheFailure(
                "KVCache: Failed to increase key {$key}."
            );
        }
        
        return $result;
    }

    public function decrease(string $key, int $step = 1): int {
    
        $result = apcu_dec($key, $step, $success);

        if (!$success) {
        
            throw new CacheFailure(
                "KVCache: Failed to decrease key {$key}."
            );
        }
        
        return $result;
    }
    
    public function keys(string $keyRules = null): array {

        $ret = [];
        if (!$keyRules || $keyRules === '*') {

            $iter = new \APCUIterator();

        } else {

            $iter = new \APCUIterator(self::compileRule($keyRules));
        }

        foreach ($iter as $key => $v) {

            $ret[] = $key;
        }

        return $ret;
    }

    public function count(string $keyRules = null): int {

        if (!$keyRules || $keyRules === '*') {

            return apcu_cache_info(true)['num_entries'];
        }

        return (new \APCUIterator(self::compileRule($keyRules)))->getTotalCount();
    }

    public function ping(): bool {

        return true;
    }
}
