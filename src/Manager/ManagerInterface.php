<?php

namespace LitePubl\Core\DB\Manager;

interface ManagerInterface
{
    public function getPrefix(): string;
    public function createTable(string $name, string $struct);
    public function deleteTable(string $name): bool;
    public function deleteAllTables(string $dbName);
    public function clearTable(string $name);
    public function alter(string $table, string $arg);
//    public function getAutoIncrement(string $table)
//    public function setAutoIncrement($table, $value)
    public function columnExists(string $table, string $column): bool;
    public function keyExists(string $table, string $key): bool;
}
