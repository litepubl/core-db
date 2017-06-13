<?php

namespace LitePubl\Core\DB;

class MysqliAdapter implements AdapterInterface
{
    protected $mysqli;

    public function __construct(\mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function getDriver()
    {
        return $this->mysqli;
    }

    public function exec(string $sql)
    {
        return $this->query($sql);
    }

    public function query(string $sql)
    {
        $result = $this->mysqli->query($sql);
        if ($result == false) {
            throw new Exception($this->mysqli->error);
        }

        if ($this->mysqli->warning_count
                && ($r = $this->mysqli->query('SHOW WARNINGS'))
                && $r->num_rows
            ) {
            $this->warning($sql, $r->fetch_assoc());
        }

        return $result;
    }

    public function quote(string $s): string
    {
        return $this->mysqli->real_escape_string($s);
    }

    public function free($res)
    {
        if ($res) {
            $res->close();
        }
    }

    public function fetchAssoc($res)
    {
        return $res ? $res->fetch_assoc() : false;
    }

    public function fetchRow($res)
    {
        return $res ? $res->fetch_row() : false;
    }

    public function fetchAll($res): array
    {
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getCount($res): int
    {
        return $res ? $res->num_rows : 0;
    }

    public function getLastId(string $tableName, string $name = 'id_seq')
    {
        if ($result = $this->mysqli->insert_id) {
            return $result;
        }

        $res = $this->mysqli->query('select last_insert_id() from ' . $tableName);
        $r = $res->fetch_row();
        $res->close();
        return $r[0];
    }

    public function beginTransaction()
    {
        $this->mysqli->autocommit(false);
    }

    public function commit()
    {
        $this->mysqli->commit();
        $this->mysqli->autocommit(true);
    }

    public function rollback()
    {
        $this->mysqli->rollback();
        $this->mysqli->autocommit(true);
    }
}
