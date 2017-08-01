<?php

namespace LitePubl\Core\DB\Adapter;

interface AdapterInterface
{
    public function getDriver();
    public function withErrorStrategy(ErrorStrategy $errorStrategy): AdapterInterface;
    public function exec(string $sql);
    public function query(string $sql);
    public function quote(string $s): string;
    public function free($res);
    public function fetchAssoc($res);
    public function fetchRow($res);
    public function fetchAll($res): array;
    public function getCount($res): int;
    public function getLastId(string $tableName, string $name = 'id_seq');
    public function beginTransaction();
    public function commit();
    public function rollback();
}
