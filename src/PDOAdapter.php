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
  
    public function query(string $sql)
    {
        $result = $this->pdo->query($sql);
        if ($result === false) {
              throw new Exception('Error db query');
        }
    }
  
    public function exec(string $sql)
    {
        return $this->pdo->exec($sql);
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

    public function getLastId($res)
    {
        $id = $this->pdo->lastInsertId('id');
        if ($id) {
            return $id;
        }
    }
}
