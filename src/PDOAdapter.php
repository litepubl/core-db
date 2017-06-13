<?php

namespace LitePubl\Core\DB;

use \PDO;

class PDOAdapter implements AdapterInterface
{
    protected $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getDriver()
    {
        return $this->pdo;
    }

    public function exec(string $sql)
    {
        $result = $this->pdo->exec($sql);
        if ($result === false) {
            $info = $this->pdo->errorInfo();
              throw new Exception($info[2]);
        }

        return $result;
    }
  
    public function query(string $sql)
    {
        $result = $this->pdo->query($sql);
        if ($result === false) {
            $info = $this->pdo->errorInfo();
              throw new Exception($info[2]);
        }

        return $result;
    }
  
    public function quote(string $s): string
    {
        return $this->pdo->quote($s);
    }

    public function free($res)
    {
        if ($res) {
            $res->closeCursor();
        }
    }

    public function fetchAssoc($res)
    {
        return $res ? $res->fetch(PDO::FETCH_ASSOC) : false;
    }
  
    public function fetchRow($res)
    {
        return $res ? $res->fetch(PDO::FETCH_NUM) : false;
    }

    public function fetchAll($res): array
    {
        return $res ? $res->fetchAll(PDO::FETCH_ASSOC) : [];
    }

  
    public function getCount($res): int
    {
        return $res ? $res->rowCount() : 0;
    }

    public function getLastId(string $tableName, string $name = 'id_seq')
    {
        return $this->pdo->lastInsertId($name);
    }

    public function beginTransaction()
    {
        $this->pdo->beginTransaction();
    }

    public function commit()
    {
        $this->pdo->commit();
    }

    public function rollback()
    {
        $this->pdo->rollback();
    }
}
