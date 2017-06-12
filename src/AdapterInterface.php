<?php

namespace LitePubl\Core\DB;

interface AdapterInterface
{
    public function getDriver();
    public function exec(string $sql);
    public function query(string $sql);
    public function quote(string $s): string;
    public function free($res);
    public function fetchAssoc($res);
    public function fetchRow($res);
    public function fetchAll($res): array;
    public function getCount($res): int;
    public function getLastId($res);
}
