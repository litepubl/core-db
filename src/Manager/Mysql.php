<?php

namespace LitePubl\Core\DB\Manager;

use LitePubl\Core\DB\DB;

class Mysql implements ManagerInterface
{
    protected $db;
    protected $engine;

    public function __construct(DB $db, string $engine = '')
    {
        $this->db = $db;
        $this->engine = $engine ? $engine : 'InnoDB        ;';
    }

    public function getPrefix(): string
    {
        return $this->db->getPrefix();
    }

    public function createTable(string $name, string $struct)
    {
        $this->deleteTable($name);
        $prefix = $this->getPrefix();

        return $this->db->exec(
            "create table $prefix$name
    ($struct)
    ENGINE=$this->engine
    DEFAULT CHARSET=utf8
    COLLATE = utf8_general_ci"
        );
    }

    public function deleteTable(string $name): bool
    {
        if ($this->tableExists($name)) {
            $prefix = $this->getPrefix();
            $this->db->exec("DROP TABLE $prefix$name");
            return true;
        }

        return false;
    }

    public function deleteAllTables(string $dbName)
    {
        $db = $this->db;
        $list = $db->res2array($db->query("show tables from " . $dbName));
        foreach ($list as $row) {
            $db->exec("DROP TABLE IF EXISTS " . $row[0]);
        }
    }

    public function clearTable(string $name)
    {
        $prefix = $this->getPrefix();
        return $this->db->exec("truncate $prefix$name");
    }

    public function alter(string $table, string $arg)
    {
        $prefix = $this->getPrefix();
        return $this->db->exec("alter table $prefix$table $arg");
    }

    public function getAutoIncrement(string $table)
    {
        $prefix = $this->getPrefix();
        $a = $this->db->fetchAssoc($this->db->query("SHOW TABLE STATUS like '$prefix$table'"));
        return $a['Auto_increment'];
    }

    public function setAutoIncrement(string $table, $value)
    {
        $prefix = $this->getPrefix();
        $this->db->exec("ALTER TABLE $prefix$table AUTO_INCREMENT = $value");
    }

    public function getEnum(string $table, string $column): array
    {
        $prefix = $this->getPrefix();
        if ($res = $this->db->query("describe $prefix$table $column")) {
            $r = $this->db->fetchAssoc($res);
            $s = $r['Type'];
            if (preg_match('/enum\((.*?)\)/i', $s, $m)) {
                $values = $m[1];
                $result = explode(',', $values);
                foreach ($result as $i => $v) {
                    $result[$i] = trim($v, ' \'"');
                }
                
                return $result;
            }
        }
        
        return null;
    }

    public function setEnum(string $table, string $column, array $enum)
    {
        $db = $this->db;
        $items = $this->quoteArray($enum);
        $default = $db->quote($enum[0]);
        $tmp = $column . '_tmp';
        $db->exec("alter table $prefix$table add $tmp enum($items) default $default");
        $db->exec("update $prefix$table set $tmp = $column + 0");
        $db->exec("alter table $prefix$table drop $column");
        $db->exec("alter table $prefix$table change $tmp $column enum($items) default $default");
    }

    public function addEnum(string $table, string $column, string $value)
    {
        if (($values = $this->getenum($table, $column)) && ! in_array($value, $values)) {
            $values[] = $value;
            $this->setEnum($table, $column, $values);
        }
    }

    public function deleteEnum(string $table, string $column, string $value)
    {
        if ($values = $this->getEnum($table, $column)) {
            $value = trim($value, ' \'"');
            $i = array_search($value, $values);
            if (false === $i) {
                return;
            }
            
            array_splice($values, $i, 1);
            $default = $values[0];
            $prefix = $this->getPrefix();
            $this->db->exec("update $prefix$table set $column = '$default' where $column = '$value'");
            
            $items = $this->quoteArray($values);
            $tmp = $column . '_tmp';
            $this->db->exec("alter table $prefix$table add $tmp enum($items)");
            foreach ($values as $name) {
                $this->db->exec("update $prefix$table set $tmp = '$name' where $column = '$name'");
            }
            $this->db->exec("alter table $prefix$table drop $column");
            $this->db->exec("alter table $prefix$table change $tmp $column enum($items)");
        }
    }

    public function renameEnum(string $table, string $column, string $oldvalue, string $newvalue)
    {
        if (($oldvalue != $newvalue) && ($values = $this->getEnum($table, $column))) {
            $db = $this->db;
            $oldvalue = trim($oldvalue, ' \'"');
            $newvalue = trim($newvalue, ' \'"');
            
            $i = array_search($oldvalue, $values);
            if (false !== $i) {
                $values[$i] = $newvalue;
                $items = $this->quoteArray($values);
                $default = $db->quote($values[0]);
                
                $prefix = $this->getPrefix();
                $tmp = $column . '_tmp';
                $db->exec("alter table $prefix$table add $tmp enum($items) default $default");
                // exclude changed
                unset($values[$i]);
                foreach ($values as $value) {
                    $value = $db->quote($value);
                    $db->exec("update $prefix$table set $tmp = $value where $column  = $value");
                }
                
                $oldvalue = $db->quote($oldvalue);
                $newvalue = $db->quote($newvalue);
                $db->exec("update $prefix$table set $tmp = $newvalue where $column  = $oldvalue");
                
                $db->exec("alter table $prefix$table drop $column");
                $db->exec("alter table $prefix$table change $tmp $column enum($items) default $default");
            }
        }
    }

    public function quoteArray(array $values): array
    {
        $db = $this->db;
        foreach ($values as $i => $value) {
            $values[$i] = $db->quote(trim($value, ' \'"'));
        }
        
        return implode(',', $values);
    }

    public function getVar(string $name): string
    {
        $v = $this->db->fetchAssoc($this->db->query("show variables like '$name'"));
        return $v['Value'];
    }

    public function setVar(string $name, string $value)
    {
        $this->db->query("set $name = $value");
    }

    public function columnExists(string $table, string $column): bool
    {
        $prefix = $this->getPrefix();
        return $this->db->query("SHOW COLUMNS FROM $prefix$table LIKE '$column'")->num_rows;
    }

    public function keyExists(string $table, string $key): bool
    {
        $prefix = $this->getPrefix();
        return $this->db->query("SHOW index FROM $prefix$table where Key_name = '$key'")->num_rows;
    }

    public function deleteColumn(string $table, string $column)
    {
        $this->alter($table, "drop $column");
    }

    public function getDatabases(): array
    {
        if ($res = $this->db->query("show databases")) {
            return $this->res2id($res);
        }

        return false;
    }

    public function dbExists($name)
    {
        if ($list = $this->GetDatabaseList()) {
            return in_array($name, $list);
        }
        return false;
    }

    public function getTables()
    {
        $prefix = $this->getPrefix();
        if ($res = $this->db->query(sprintf("show tables from %s like '%s%%'", $this->dbname, $prefix))) {
            return $this->res2id($res);
        }
        return false;
    }

    public function tableExists(string $name): bool
    {
        if ($list = $this->gettables()) {
            return in_array($this->getPrefix() . $name, $list);
        }
        return false;
    }

    public function createdatabase($name)
    {
        if ($this->dbexists($name)) {
            return false;
        }
        
        return $this->db->exec("CREATE DATABASE $name");
    }

    public function optimize()
    {
        $db = $this->db;
        $prefix = strtolower($this->getPrefix());
        $tables = $this->getTables();
        foreach ($tables as $table) {
            if (Str::begin(strtolower($table), $prefix)) {
                $db->exec("LOCK TABLES `$table` WRITE");
                $db->exec("OPTIMIZE TABLE $table");
                $db->exec("UNLOCK TABLES");
            }
        }
    }
}
