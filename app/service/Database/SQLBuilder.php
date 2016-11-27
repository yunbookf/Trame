<?php
declare (strict_types = 1);
/**
 * 条件规则
 * 
 * '#or'  => ['a' => 1, 'b' => 2]   -> '(a = 1 OR b = 2)'
 * '#and' => ['a' => 1, 'b' => 2]   -> '(a = 1 AND b = 2)'
 * 
 * '~x' => '/abc/'   ->   'x REGEXP "/adfg/"'
 * '%x' => '/%f%/'   ->   'x LIKE "%f%/"'
 */
namespace T\TDBI;

use \T\Msg\SQLFailure;

class SQLBuilder {

    const SQL_AC_SELECT = 0;

    const SQL_AC_INSERT = 1;

    const SQL_AC_UPDATE = 2;

    const SQL_AC_DELETE = 3;

    const SQL_AC_INSERT_MULTI = 4;

    protected $sql;

    protected $action;

    protected $fields;

    protected $table;

    protected $where;

    protected $updates;

    protected $joins;

    protected $limit;

    protected $limitOffset;

    protected $orders;

    public function select($fields = null): SQLBuilder {

        $this->action = self::SQL_AC_SELECT;

        if (is_array($fields)) {

            $this->fields = join(',', $fields);
        }
        elseif (is_string($fields)) {

            $this->fields = $fields;
        }
        elseif ($fields === null) {

            $this->fields = '*';
        }

        return $this;

    }

    public function insert(array $fields = null): SQLBuilder {

        $this->action = self::SQL_AC_SELECT;

        if (is_array($fields)) {

            $this->fields = $fields;
        }

        return $this;

    }

    public function join(string $table, string $type = 'INNER'): SQLBuilder {

        if (!$this->joins) {

            $this->joins = [];
        }

        $this->joins[] = [
            'table' => $table,
            'type' => $type,
            'on' => ''
        ];

        return $this;
    }

    public function into(string $table): SQLBuilder {

        $this->table = $table;

        return $this;
    }

    public function from(string $table): SQLBuilder {

        $this->table = $table;

        return $this;
    }

    public function limit(int $number, int $offset = null): SQLBuilder {

        $this->limitOffset = $offset;

        $this->limit = $number;
    }

    public function orderBy(array $orders): SQLBuilder {

        $this->orders = $orders;
    }

    protected function cleanUp() {

        unset(
            $this->fields,
            $this->joins,
            $this->where,
            $this->table,
            $this->updates,
            $this->limit,
            $this->limitOffset,
            $this->orders
        );
    }
    protected static function escapeValue($v): string {

        if (is_array($v)) {

            throw new SQLFailure(
                'SQLBuilder: Array shouldn\'t be used with complex expression.'
            );
        }

        if ($v === null) {

            return 'null';
        }
        elseif (is_string($v)) {

            return '"' . addslashes($v) . '"';
        }
        elseif (is_bool($v)) {

            return $v ? 'true' : 'false';
        }
        else {

            return "{$v}";
        }

    }

    protected static function compileSimpleExpr(
        string $key,
        $value,
        bool $neg = false
    ): string {

        if (is_array($value)) {

            foreach ($value as &$item) {

                if (is_string($item)) {

                    $item = self::escapeValue($item);
                }
                else {

                    $item += 0;
                }
            }

            $value = join(',', $value);

            if ($neg) {

                return "{$key} NOT IN ({$value})";
            }
            else {

                return "{$key} IN ({$value})";
            }
        }
        elseif ($value === null) {

            if ($neg) {

                return $key . ' IS NOT NULL';
            }
            else {

                return $key . ' IS NULL';
            }
        }
        else {

            $value = self::escapeValue($value);

            if ($neg) {

                return "{$key} <> {$value}";
            }
            else {

                return "{$key} = {$value}";
            }
        }

    }

    protected static function compileAdvanceExpr(
        string $key,
        $value,
        bool $neg = false
    ): string {

        switch ($key[0]) {
        case '~':

            return substr($key, 1) . ($neg ? ' NOT LIKE ' : ' LIKE ') .
            self::escapeValue($value);

        case '>':

            return substr($key, 1) . ($neg ? ' >= ' : ' < ') .
            self::escapeValue($value);

        case '<':

            return substr($key, 1) . ($neg ? ' <= ' : ' > ') .
            self::escapeValue($value);

        case '@':

            return substr($key, 1) . ($neg ? ' <> ' : ' = ') . $value;

        default:

            return self::compileSimpleExpr($key, $value, $neg);
        }

    }

    protected static function compileConditions(
        array $conds,
        string $dep,
        bool $wrap = true
    ): string {

        $items = [];

        foreach ($conds as $key => $value) {

            if (is_int($key)) {

                $items[] = $value;

                continue;
            }
            switch ($key[0]) {
            case '#':
                switch (substr($key, 1)) {
                case 'or': // 条件或集

                    if (!is_array($value)) {

                        throw new SQLFailure(
                            "SQLBuilder: '{$key}' must be used with conditions array!"
                        );
                    }

                    $items[] = self::compileConditions($value, ' OR ');

                    break;

                case 'and': // 条件和集

                    if (!is_array($value)) {

                        throw new SQLFailure(
                            "SQLBuilder: '{$key}' must be used with conditions array!"
                        );
                    }

                    $items[] = self::compileConditions($value, ' AND ');

                    break;

                default:

                    throw new SQLFailure(
                    "Invalid syntax for SQLBuilder with field '{$key}'.");
                }

                break;

            case '@':

                $items[] = substr($key, 1) . ' = ' . $value;

                break;

            case '!':

                $items[] = self::compileAdvanceExpr(substr($key, 1), $value,
                true);

                break;

            default:

                $items[] = self::compileAdvanceExpr($key, $value);
            }
        }

        return $wrap ? '(' . join($dep, $items) . ')' : join($dep, $items);

    }

    public function on(array $conds): SQLBuilder {

        $this->joins[count($this->joins) - 1]['on'] = self::compileConditions($conds, ' AND ', false);

        return $this;

    }

    public function where(array $conds): SQLBuilder {

        $this->where = self::compileConditions($conds, ' AND ', false);

        return $this;
    }

    protected function genLimitString(): string {

        if ($this->limit) {

            if ($this->limitOffset) {

                return " LIMIT {$this->limit}";

            } else {

                return " LIMIT {$this->limitOffset}, {$this->limit}";
            }
        }
        
        return '';
    }

    protected function genOrderString(): string {

        if (!$this->orders) {

            return '';
        }

        $orders = [];

        foreach ($this->orders as $order) {

            $orders[] = $order['field'] . ' ' . $order['method'];
        }

        return ' ORDER BY ' . join(',', $orders);
    }

    protected function genJoinString(): string {

        if ($this->joins) {

            $joins = [];

            foreach ($this->joins as &$join) {

                $joins[] = "{$join['type']} JOIN {$join['table']} ON {$join['on']}";
            }

            return ' ' . join(' ', $joins) . ' ';
        }

        return '';
    }

    protected function genSelectSQL() {

        $where = $this->where ? ' WHERE ' . $this->where : '';
        $join = $this->genJoinString();
        $limit = $this->genLimitString();
        $order = $this->genOrderString();

        $this->sql = "SELECT {$this->fields} FROM {$this->table}{$join}{$where}{$order}{$limit}";

    }

    protected function genDeleteSQL() {

        $where = $this->where ? ' WHERE ' . $this->where : '';
        $join = $this->genJoinString();
        $limit = $this->genLimitString();

        $this->sql = "DELETE FROM {$this->table}{$where}{$limit}";

    }

    public function end(): SQLBuilder {

        if ($this->sql) {
            return $this;
        }

        if (!isset($this->table, $this->action)) {

            throw new SQLFailure(

                'SQLBuilder: SQL is not completed.'
            );
        }

        switch ($this->action) {
        case self::SQL_AC_DELETE: $this->genDeleteSQL(); break;

        case self::SQL_AC_SELECT: $this->genSelectSQL(); break;

        case self::SQL_AC_UPDATE: $this->genUpdateSQL(); break;

        case self::SQL_AC_INSERT: $this->genInsertSQL(); break;

        case self::SQL_AC_INSERT_MULTI: $this->genMInsertSQL(); break;
        }

        $this->cleanUp();
        return $this;
    }

    public function getSQL(): string {

        if (!$this->sql) {

            throw new SQLFailure(

                'SQLBuilder: SQL is not completed.'
            );
        }

        return $this->sql;
    }
}
