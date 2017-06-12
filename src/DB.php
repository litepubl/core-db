<?php

namespace LitePubl\Core\DB;

class DB implements DBInterface
{
    protected $adapter;
    protected $result;
    protected $sql;
    protected $history;
    public $table;
    public $prefix;

    public function __construct(AdapterInterface $adapter, string $prefix)
    {
        $this->adapter = $adapter;
        $this->prefix = $prefix;
        $this->sql = '';
        $this->table = '';
        $this->history = [];
    }

    public function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    public function __get($name)
    {
        if ($name == 'man') {
            return DBManager::i();
        }

        return $this->prefix . $name;
    }

    public function exec(string $sql)
    {
        return $this->adapter->exec($sql);
    }

    public function query(string $sql)
    {
        $this->sql = $sql;
        if (Config::$debug) {
            $this->history[] = [
                'sql' => $sql,
                'time' => 0
            ];
            $microtime = microtime(true);
        }

        if (is_object($this->result)) {
            $this->adapter->free($this->result);
        }

        $this->result = $this->adapter->query($sql);

        if ($this->result == false) {
            $this->error($this->mysqli->error);
        } elseif (Config::$debug) {
            $this->history[count($this->history) - 1]['time'] = microtime(true) - $microtime;
            if ($this->mysqli->warning_count
                && ($r = $this->mysqli->query('SHOW WARNINGS'))
                && $r->num_rows
            ) {
                $this->getApp()->getLogger()->warning($sql, $r->fetch_assoc());
            }
        }

        return $this->result;
    }

    protected function error(string $mesg)
    {
        $mesg .= "\n$this->sql\n";
        if (Config::$debug) {
                $mesg .= $this->performance();
        }

        throw new DBException($mesg);
    }

    public function performance(): string
    {
        $result = '';
        $total = 0.0;
        $max = 0.0;
                $maxsql = '';
        foreach ($this->history as $i => $item) {
            $result.= "$i: {$item['time']}\n{$item['sql']}\n\n";
            $total+= $item['time'];
            if ($max < $item['time']) {
                $maxsql = $item['sql'];
                $max = $item['time'];
            }
        }
        $result.= "maximum $max\n$maxsql\n";
        $result.= sprintf("%s total time\n%d querries\n\n", $total, count($this->history));
        return $result;
    }

    public function quote($s): string
    {
        return sprintf('\'%s\'', $this->adapter->quote($s));
    }

    public function escape($s): string
    {
        return $this->adapter->quote($s);
    }

    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    public function select(string $where)
    {
        if ($where) {
            $where = 'where ' . $where;
        }

        return $this->query("SELECT * FROM $this->prefix$this->table $where");
    }

    public function idSelect(string $where)
    {
        return $this->res2id($this->query("select id from $this->prefix$this->table where $where"));
    }

    public function selectAssoc(string $sql)
    {
        $res = $this->query($sql);
        return $this->adapter->fetchAssoc($res);
    }

    public function getAssoc(string $where)
    {
        $res = $this->select($where);
        return $this->adapter->fetchAssoc($res);
    }

    public function update($values, $where)
    {
        return $this->exec("update $this->prefix$this->table set $values   where $where");
    }

    public function idUpdate($id, $values)
    {
        return $this->update($values, "id = $id");
    }

    public function assoc2update(array $a)
    {
        $list = [];
        foreach ($a as $name => $value) {
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
                $list[] = sprintf('%s = %s ', $name, $value); {
                    continue;
                }
            }

            $list[] = sprintf('%s = %s', $name, $this->quote($value));
        }

        return implode(',', $list);
    }

    public function updateAssoc(array $a, $index = 'id')
    {
        $id = $a[$index];
        unset($a[$index]);
        return $this->update($this->assoc2update($a), "$index = '$id' limit 1");
    }

    public function setValues($id, array $values)
    {
        return $this->update($this->assoc2update($values), "id = '$id' limit 1");
    }

    public function insertRow($row)
    {
        return $this->exec(sprintf('INSERT INTO %s%s %s', $this->prefix, $this->table, $row));
    }

    public function insertAssoc(array $a)
    {
        unset($a['id']);
        return $this->add($a);
    }

    public function addUpdate(array $a)
    {
        if ($this->idexists($a['id'])) {
            $this->updateAssoc($a);
        } else {
            return $this->add($a);
        }
    }

    public function add(array $a)
    {
        $res = $this->insertRow($this->assocToRow($a));
        return $this->adapter->getLastId();
    }

    public function insert(array $a)
    {
        $this->insertRow($this->assocToRow($a));
    }

    public function assocToRow(array $a)
    {
        $vals = [];
        foreach ($a as $val) {
            if (is_bool($val)) {
                $vals[] = $val ? '1' : '0';
            } else {
                $vals[] = $this->quote($val);
            }
        }

        return sprintf('(%s) values (%s)', implode(',', array_keys($a)), implode(',', $vals));
    }

    public function getCount($where = '')
    {
        $sql = "SELECT COUNT(*) as count FROM $this->prefix$this->table";
        if ($where) {
            $sql.= ' where ' . $where;
        }

        $res = $this->query($sql);
        if ($res) {
                $r = $this->adapter->fetchAssoc($res);
            if ($r) {
                    return (int)$r['count'];
            }
        }

        return false;
    }

    public function delete($where)
    {
        return $this->exec("delete from $this->prefix$this->table where $where");
    }

    public function idDelete($id)
    {
        return $this->exec("delete from $this->prefix$this->table where id = $id");
    }

    public function deleteItems(array $items)
    {
        return $this->delete('id in (' . implode(',', $items) . ')');
    }

    public function idExists($id): bool
    {
        $res = $this->query("select id  from $this->prefix$this->table where id = $id limit 1");
        if ($res) {
                $r = $this->adapter->fetchAssoc($res);
                return count($r) > 0;
        }

        return false;
    }

    public function exists(string $where): bool
    {
        $res = $this->query("select *  from $this->prefix$this->table where $where limit 1");
        return (bool) $this->adapter->getCount($res);
    }

    public function getList(array $list)
    {
        return $this->res2assoc($this->select(sprintf('id in (%s)', implode(',', $list))));
    }

    public function getItems($where)
    {
        return $this->res2assoc($this->select($where));
    }

    public function getItem($id, $propname = 'id')
    {
        if ($res = $this->query("select * from $this->prefix$this->table where $propname = $id limit 1")) {
            return $this->adapter->fetchAssoc($res);
        }

        return false;
    }

    public function findItem($where)
    {
        $res = $this->query("select * from $this->prefix$this->table where $where limit 1")->fetch_assoc();
        return $this->adapter->fetchAssoc($res);
    }

    public function findId($where)
    {
        return $this->findprop('id', $where);
    }

    public function findProp($propname, $where)
    {
        if ($res = $this->query("select $propname from $this->prefix$this->table where $where limit 1")) {
            if ($r = $this->adapter->fetchAssoc($res)) {
                        return $r[$propname];
            }
        }

        return false;
    }

    public function getVal(string $table, $id, $name)
    {
        if ($res = $this->query("select $name from $this->prefix$table where id = $id limit 1")) {
            if ($r = $this->adapter->fetchAssoc($res)) {
                        return $r[$name];
            }
        }

        return false;
    }

    public function getValue($id, $name)
    {
        if ($res = $this->query("select $name from $this->prefix$this->table where id = $id limit 1")) {
            if ($r = $this->adapter->fetchAssoc($res)) {
                        return $r[$name];
            }
        }

        return false;
    }

    public function setValue($id, $name, $value)
    {
        return $this->update("$name = " . $this->quote($value), "id = $id");
    }

    public function getValues($names, $where)
    {
        $result = [];
        $res = $this->query("select $names from $this->prefix$this->table where $where");
        if (is_object($res)) {
            while ($r = $this->adapter->fetchRow($res)) {
                $result[$r[0]] = $r[1];
            }
        }

        return $result;
    }

    public function res2array($res): array
    {
        $result = [];
        if (is_object($res)) {
            while ($row = $this->adapter->fetchRow($res)) {
                $result[] = $row;
            }
        }

            return $result;
    }

    public function res2id($res)
    {
        $result = [];
        if (is_object($res)) {
            while ($row = $this->adapter->fetchRow($res)) {
                $result[] = $row[0];
            }
        }

        return $result;
    }

    public function res2assoc($res): array
    {
        return $this->adapter->fetchAll($res);
    }

    public function res2items($res)
    {
        $result = [];
        if (is_object($res)) {
            while ($r = $this->adapter->fetchAssoc($res)) {
                $result[(int)$r['id']] = $r;
            }
        }

        return $result;
    }

    public function fetchAssoc($res)
    {
        return $this->adapter->fetchAssoc($res);
    }

    public function fetchNum($res)
    {
        return $this->adapter->fetchRow($res);
    }

    public function countof($res)
    {
        return $this->adapter->getCount($res);
    }
}
