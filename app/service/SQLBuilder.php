<?php

declare (strict_types = 1);

namespace T\Service;

/**
 * 用于处理一些网络请求
 *
 * @author Han Guo Shuai
 *
 */
class SQLBuilder extends IService {

    public $sql = '';

    // --- 基础语句 ---

    /**
     * 插入的 sql 语句
     *
     * @param string $f
     *
     * @param array $cs
     *
     * @param array $vs
     *
     * @return SQLBuilder
     */
    public function insert(string $f,array $cs = array(), array $vs = array()) {
        $this->sql = 'INSERT' . ' INTO ';
        if (is_string($f)) $this->sql .= $this->pre . $f . ' (';
        if (count($vs) > 0) {
            // --- "shop", ['name', 'address'], ['猎云酒店', '云之路29号'] ---
            foreach ($cs as $i)
                $this->sql .= $i . ',';
            $this->sql = substr($this->sql, 0, -1) . ') VALUES ';
            // --- 判断插入单条记录还是多条记录 ---
            if (is_array($vs[0])) {
                // --- 多条记录 ---
                foreach ($vs as $is) {
                    $this->sql .= '(';
                    foreach ($is as $i)
                        $this->sql .= $this->quote($i) . ',';
                    $this->sql = substr($this->sql, 0, -1) . '), ';
                }
                $this->sql = substr($this->sql, 0, -2);
            } else {
                // --- 单条记录 ---
                $this->sql .= '(';
                foreach ($vs as $i)
                    $this->sql .= $this->quote($i) . ',';
                $this->sql = substr($this->sql, 0, -1) . ')';
            }
        } else {
            $values = '';
            if(is_int(key($cs))) {
                // --- "shop", ['name', 'address'] ---
                // --- prepare ---
                foreach ($cs as $val) {
                    $this->sql .= $val . ',';
                    $values .= ':'.$val.',';
                }
            } else {
                // --- "shop", ['name' => '猎云酒店', 'address' => '云之路29号'] ---
                foreach ($cs as $key => $val) {
                    $this->sql .= $key . ',';
                    $values .= $this->quote($val) . ',';
                }
            }
            $this->sql = substr($this->sql, 0, -1) . ') VALUES (' . substr($values, 0, -1) . ')';
        }
        return $this;
    }

    /**
     * 选择的 sql 语句
     *
     * @param string | array $c
     *
     * @param string $f
     *
     * @return SQLBuilder
     */
    public function select($c, string $f) {
        $this->sql = 'SELECT ';
        if (is_string($c)) $this->sql .= $c;
        else if (is_array($c)) {
            foreach ($c as $i) $this->sql .= $i . ',';
            $this->sql = substr($this->sql, 0, -1);
        }
        $this->sql .= ' FROM ' . $this->pre . $f;
        return $this;
    }

    /**
     * 更新的 sql 语句
     *
     * @param string $f
     *
     * @param array $s
     *
     * @return SQLBuilder
     *
     * @throws
     */
    public function update($f, $s = array()) {
        $this->sql = 'UPDATE ' . $this->pre . $f . ' SET ';
        foreach ($s as $k => $i) {
            if(is_int($k) && is_string($i))
                $this->sql .= $i . ' = :'.$i.',';
            else if (is_string($i) || is_numeric($i))
                $this->sql .= $k . ' = ' . $this->quote($i) . ',';
            else if (is_array($i)) {
                if (isset($i[2]))
                    $this->sql .= $i[0] . ' = ' . $i[0] . ' ' . $i[1] . ' ' . $this->quote($i[2]) . ',';
                else
                    $this->sql .= $i[0] . ' = ' . $i[0] . ' ' . $i[1] . ' :' . $i[0] . ',';
            } else
                throw new \Exception('Error, Sql, Update, ' . gettype($i));
        }
        $this->sql = substr($this->sql, 0, -1);
        return $this;
    }

    public function delete($f) {
        $this->sql = 'DELETE ' . 'FROM ' . $this->pre . $f;
        return $this;
    }

    // --- 筛选器 ---

    // --- 1.['city', 'type'] ---
    // --- 2.['city' => 'bj', 'type' => '2'] ---
    // --- 3.['city', ['type', '>']] ---
    // --- 4.['city' => 'bj', ['type', '>', '1']] ---
    // --- 5.[['city', '='], ['type', '>', '1']] ---
    // --- 6.['type', '>=', '3'] --- 此条和 1 冲突暂无法实现 ---
    public function where($s) {
        $this->sql .= ' WHERE ';
        foreach ($s as $k => $i) {
            // --- 1, 3(前1) ---
            if(is_int($k) && is_string($i))
                $this->sql .= $i . ' = :' . $i . ' AND ';
            // --- 2, 4(前1) ---
            else if (is_string($i) || is_numeric($i))
                $this->sql .= $k . ' = ' . $this->quote($i) . ' AND ';
            // --- 3(后1), 4(后1), 5 ---
            else if (is_array($i)) {
                if (strtolower($i[1]) == 'in') {
                    $this->sql .= $i[0] . ' IN (';
                    foreach ($i[2] as $v)
                        $this->sql .= $this->quote($v) . ', ';
                    $this->sql = substr($this->sql, 0, -2) . ') AND ';
                } else
                    if(isset($i[2]))
                        $this->sql .= $i[0] . ' ' . $i[1] . ' ' . $this->quote($i[2]) . ' AND ';
                    else
                        $this->sql .= $i[0] . ' ' . $i[1] . ' :' . $i[0] . ' AND ';
            } else
                throw new \Exception('[MyX - L(Mysql) Error] only support string or array, but yours type is ' . gettype($i));
        }
        $this->sql = substr($this->sql, 0, -5);
        return $this;
    }
    private function _where(array $arr) {

    }

    public function by($c, $d = 'DESC') {
        $this->sql .= ' ORDER BY ';
        if (is_string($c)) $this->sql .= $c . ' ' . $d;
        else if (is_array($c)) {
            foreach ($c as $k => $v) {
                $this->sql .= $v . ',';
            }
            $this->sql = substr($this->sql, 0, -1) . ' ' . $d;
        }
        return $this;
    }

    public function groupBy($c) {
        $this->sql .= ' GROUP BY ' . $c;
        return $this;
    }

    public function limit($a, $b) {
        $this->sql .= ' LIMIT ' . $a . ', ' . $b;
        return $this;
    }

}

