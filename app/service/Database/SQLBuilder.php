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

abstract class ISQLGenerator {

    protected static function escapeValue($v, bool $isVar = false): string {
    
        if (is_array($v)) {
    
            if (isset($v['$var'])) {
    
                return ':' . $v['$var'];
            }
            else {
    
                throw new SQLFailure(
                    'SQLBuilder: Array shouldn\'t be used with complex expression.'
                );
            }
        }
        elseif ($isVar) {
    
            return ':' . $v;
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
}

class SQLMultiInsert extends ISQLGenerator {

    protected $prefix;

    protected $fields;

    protected $inserts;

    public function __construct(string $sqlPrefix, array $fields) {

        $this->prefix = $sqlPrefix;
        $this->fields = $fields;
    }

    public function reset() {

        $this->inserts = [];
    }

    public function multiValues(array $data): SQLMultiInsert {

        foreach ($data as $row) {

            $this->values($row);
        }

        return $this;
    }

    public function values(array $row): SQLMultiInsert {

        if (!$this->inserts) {

            $this->inserts = [];
        }

        $tmp = [];

        foreach ($this->fields as $field) {

            $tmp[] = self::escapeValue($row[$field]);
        }

        $this->inserts[] = '(' . join(',', $tmp) . ')';

        return $this;
    }

    public function getSQL(): string {

        return $this->prefix . join(',', $this->inserts);
    }

    public function __toString(): string {

        return $this->getSQL();
    }
}

abstract class ISQLBuilder extends ISQLGenerator {

    const SQL_AC_SELECT = 0;

    const SQL_AC_INSERT = 1;

    const SQL_AC_UPDATE = 2;

    const SQL_AC_DELETE = 3;

    const SQL_AC_INSERT_MULTI = 4;

    protected $sql;

    protected $action;

    protected $fields;

    public function end(): ISQLBuilder {
    
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

        case self::SQL_AC_INSERT: 

        case self::SQL_AC_INSERT_MULTI: $this->genInsertSQL(); break;
        }
    
        $this->cleanUp();
        return $this;
    }

    /**
     * 
     * @return string | \T\TDBI\SQLMultiInsert
     */
    public function getSQL() {
    
        if (!$this->sql) {
    
            $this->end();
        }

        if ($this->action === self::SQL_AC_INSERT_MULTI) {

            return new SQLMultiInsert($this->sql, $this->fields);
        }
        else {

            return $this->sql;
        }
    }

    abstract protected function genInsertSQL();

    abstract protected function genUpdateSQL();

    abstract protected function genSelectSQL();

    abstract protected function genDeleteSQL();

    abstract public function select($fields = null): ISQLBuilder;

    abstract public function update(): ISQLBuilder;

    abstract public function delete(): ISQLBuilder;

    abstract public function insert(array $fields = null): ISQLBuilder;

    abstract public function multiValues(array $rows): ISQLBuilder;

    abstract public function values(array $keyValues): ISQLBuilder;

    abstract public function join(string $table, string $type = 'INNER'): ISQLBuilder;

    abstract public function into(string $table): ISQLBuilder;

    abstract public function from(string $table): ISQLBuilder;

    abstract public function limit(int $number, int $offset = null): ISQLBuilder;

    abstract public function orderBy(array $orders): ISQLBuilder;

    abstract public function on(array $conds): ISQLBuilder;

    abstract public function where(array $conds): ISQLBuilder;

    abstract public function set(array $assigns): ISQLBuilder;

    abstract protected function cleanUp();
}

class SQLBuilder extends ISQLBuilder {

    protected $table;

    protected $where;

    protected $updates;

    protected $joins;

    protected $limitNum;

    protected $limitOffset;

    protected $orders;

    protected $inserts;

    public function select($fields = null): ISQLBuilder {

        $this->action = self::SQL_AC_SELECT;

        if (is_array($fields)) {

            foreach ($fields as $key => &$f) {

                if (is_string($key)) {

                    $f = $key . ' as ' . $f;
                }
            }

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

    public function update(): ISQLBuilder {

        $this->action = self::SQL_AC_UPDATE;

        return $this;
    }

    public function delete(): ISQLBuilder {

        $this->action = self::SQL_AC_DELETE;

        return $this;
    }

    public function insert(array $fields = null): ISQLBuilder {

        if (is_array($fields)) {

            $this->action = self::SQL_AC_INSERT_MULTI;
            $this->fields = $fields;
        }
        else {

            $this->action = self::SQL_AC_INSERT;
        }

        return $this;

    }

    public function multiValues(array $rows): ISQLBuilder {

        foreach ($rows as $row) {

            $this->values($row);
        }

        return $this;
    }

    public function values(array $keyValues): ISQLBuilder {

        if (!$this->inserts) {

            $this->inserts = [];
        }

        if (!$this->fields) {

            $this->fields = array_keys($keyValues);
        }

        $row = [];

        foreach ($this->fields as $key) {

            $row[] = self::escapeValue($keyValues[$key]);
        }

        $this->inserts[] = '(' . join(',', $row) . ')';

        return $this;
    }

    public function join(string $table, string $type = 'INNER'): ISQLBuilder {

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

    public function into(string $table): ISQLBuilder {

        $this->table = $table;

        return $this;
    }

    public function from(string $table): ISQLBuilder {

        $this->table = $table;

        return $this;
    }

    public function limit(int $number, int $offset = null): ISQLBuilder {

        $this->limitOffset = $offset;

        $this->limitNum = $number;

        return $this;
    }

    public function orderBy(array $orders): ISQLBuilder {

        $this->orders = $orders;
    }

    protected function cleanUp() {

        unset(
            $this->joins,
            $this->where,
            $this->table,
            $this->updates,
            $this->limitNum,
            $this->limitOffset,
            $this->orders
        );

        if ($this->action === self::SQL_AC_INSERT_MULTI) {

            unset($this->inserts);
        }
        else {

            unset($this->fields);
        }
    }

    protected static function compileCondExpr(
        string $field,
        string $rel,
        $value
    ): string {

        $isVar = ($rel[0] === '%');

        if ($isVar) {

            $rel = substr($rel, 1);
        }

        switch ($rel) {
        case '$ge':

            return $field . ' >= ' . self::escapeValue($value, $isVar);

        case '$le':

            return $field . ' <= ' . self::escapeValue($value, $isVar);

        case '$gt':

            return $field . ' > ' . self::escapeValue($value, $isVar);

        case '$lt':

            return $field . ' < ' . self::escapeValue($value, $isVar);

        case '$eq':

            if ($value === null) {

                return $field . ' IS NULL';
            }
            else {

                return $field . ' = ' . self::escapeValue($value, $isVar);
            }
            

        case '$ne':

            if ($value === null) {

                return $field . ' IS NOT NULL';
            }
            else {

                return $field . ' <> ' . self::escapeValue($value, $isVar);
            }

        case '$in':
        case '$notIn':
        case '$except':

            foreach ($value as &$item) {
            
                if (is_string($item)) {
            
                    $item = self::escapeValue($item);
                }
                else {
            
                    $item += 0;
                }
            }
            
            $value = join(',', $value);
        
            return $rel == '$in' ? "{$field} IN ({$value})" : "{$field} NOT IN ({$value})";

        case '$nlike':
        case '$notLike':

            return $field . ' NOT LIKE ' . self::escapeValue($value, $isVar);

        case '$like':

            return $field . ' LIKE ' . self::escapeValue($value, $isVar);

        case '$var':

            return $field . ' = :' . $value;

        case '$or':

            return self::compileConditions($value, ' OR ');

        case '$notOr':

            return 'NOT ' . self::compileConditions($value, ' OR ');

        case '$and':

            return self::compileConditions($value, ' AND ');

        case '$notAnd':

            return 'NOT ' . self::compileConditions($value, ' AND ');

        default:

            throw new SQLFailure(
                "Unexpected action {$rel} found."
            );
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

            if ($key[0] === '$') {

                switch (substr($key, 1)) {
                case 'notOr':
                case 'or':
                case 'and':
                case 'notAnd':

                    $items[] = self::compileCondExpr('', $key, $value);
                    break;

                default:

                    throw new SQLFailure(
                        "Unexpected action {$key} found."
                    );
                }
            }
            elseif ($key[0] === '%') {

                if (!is_string($value)) {

                    throw new SQLFailure(
                        'An array cannot be used as a variable in SQL.'
                    );
                }

                $items[] = substr($key, 1) . ' = :' . $value;
            }
            elseif (is_array($value)) {
            
                foreach ($value as $rel => $v) {
            
                    $items[] = self::compileCondExpr($key, $rel, $v);
                }
            }
            else {
            
                $items[] = self::compileCondExpr($key, '$eq', $value);
            }
        }

        return $wrap ? '(' . join($dep, $items) . ')' : join($dep, $items);
    }

    public function on(array $conds): ISQLBuilder {

        $this->joins[count($this->joins) - 1]['on'] = self::compileConditions($conds, ' AND ', false);

        return $this;

    }

    public function where(array $conds): ISQLBuilder {

        $this->where = self::compileConditions($conds, ' AND ', false);

        return $this;
    }

    public function set(array $assigns): ISQLBuilder {

        if (!$this->updates) {

            $this->updates = [];
        }

        foreach ($assigns as $key => $value) {

            if (is_int($key)) {

                $this->updates[] = $value;

                continue;
            }

            switch ($key[0]) {
                case '*':

                    $this->updates[] = substr($key, 1) . ' = ' . $value;

                    break;

                case '%':

                    $this->updates[] = substr($key, 1) . ' = :' . $value;

                    break;

                default:

                    $this->updates[] = $key . ' = ' . self::escapeValue($value);
            }
        }

        return $this;
    }

    protected function genLimitString(): string {

        if ($this->limitNum) {

            if ($this->limitOffset) {

                return " LIMIT {$this->limitOffset}, {$this->limitNum}";

            } else {

                return " LIMIT {$this->limitNum}";
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

    protected function genUpdateSQL() {

        $where = $this->where ? ' WHERE ' . $this->where : '';
        $join = $this->genJoinString();
        $limit = $this->genLimitString();
        $order = $this->genOrderString();

        $sets = join(',', $this->updates);

        $this->sql = "UPDATE {$this->table}{$join} SET {$sets}{$where}{$limit}";
    }

    protected function genSelectSQL() {

        $where = $this->where ? ' WHERE ' . $this->where : '';
        $join = $this->genJoinString();
        $limit = $this->genLimitString();
        $order = $this->genOrderString();

        $this->sql = "SELECT {$this->fields} FROM {$this->table}{$join}{$where}{$order}{$limit}";
    }

    protected function genInsertSQL() {

        $fields = join(',', $this->fields);

        $this->sql = "INSERT INTO {$this->table}({$fields}) VALUES";
        if ($this->action != self::SQL_AC_INSERT_MULTI) {

            $this->sql .= join(',', $this->inserts);
        }
        else {

            $this->sql = "INSERT INTO {$this->table}({$fields}) VALUES";
        }
    }

    protected function genDeleteSQL() {

        $where = $this->where ? ' WHERE ' . $this->where : '';
        $limit = $this->genLimitString();

        $this->sql = "DELETE FROM {$this->table}{$where}{$limit}";
    }

}
