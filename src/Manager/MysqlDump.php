<?php

namespace LitePubl\Core\DB\Manager;

use LitePubl\Core\DB\DB;
use LitePubl\Core\DB\Adapter\NullErrorStrategy;

class MysqlDump implements DumpInterface
{
    const HEADER = "-- LitePubl dump\n" .
    "-- Datetime: %s\n" .
    "-- Host: %s\n" .
    "-- Database: %s\n\n" .
    "/*!40101 SET NAMES utf8 */;\n\n";

    const FOOTER = "\n-- LitePubl dump end\n";

    const TABLE_HEADER = "LOCK TABLES `%1\$s` WRITE;\n" .
    "/*!40000 ALTER TABLE `%1\$s` DISABLE KEYS */;\n";

    const TABLE_FOOTER = "/*!40000 ALTER TABLE `%1\$s` ENABLE KEYS */;\n" .
    "UNLOCK TABLES;\n\n";

    const DROP = "DROP TABLE IF EXISTS `%s`;\n";

    const INSERT =                     "INSERT INTO `%s` VALUES %s;\n";

    protected $db;
    protected $manager;
    protected $engine;
    protected $max_allowed_packet;

    public function __construct(DB $db, ManagerInterface $manager, string $engine = '')
    {
        $this->db = $db;
        $this->manager = $manager;
        $this->engine = $engine ? $engine : 'InnoDB        ;';
    }

    public function export(): string
    {
        //use adapter to prevent strange warning
        $adapter = $this->db->getAdapter()->withErrorStrategy(new NullErrorStrategy());
        $v = $adapter->fetchAssoc($adapter->query("show variables like 'max_allowed_packet'"));
        $this->max_allowed_packet = floor($v['Value'] * 0.8);
        
        $result = sprintf(static::HEADER, date('Y-m-d H:i:s'), $this->db->getDBName());

        $tables = $this->manager->getTables();
        foreach ($tables as $table) {
            $result .= $this->exportTable($table);
        }

        $result .= static::FOOTER;
        return $result;
    }

    public function exportTable(string $name): string
    {
        $db = $this->manager->getDB();
        if ($row = $db->fetchRow($db->query("show create table `$name`"))) {
            $result = sprintf(static::DROP, $name);
            $result .= $row[1];
            $result .= "\n\n";

            $res = $db->query("select * from `$name`");
            if ($db->getCountRows($res) > 0) {
                $result .= sprintf(static::TABLE_HEADER, $name);

                $sql = '';
                while ($row = $db->fetchRow($res)) {
                    $values = [];
                    foreach ($row as $v) {
                        $values[] = is_null($v) ? 'NULL' : $db->quote($v);
                    }

                    if ($sql) {
                        $sql .= ',';
                    }

                    $sql .= sprintf('(%s)', implode(',', $values));
                    if (strlen($sql) > $this->max_allowed_packet) {
                        $result .= sprintf(static::INSERT, $name, $sql);
                        $sql = '';
                    }
                }
                
                if ($sql) {
                        $result .= sprintf(static::INSERT, $name, $sql);
                }

                $result .= sprintf(static::TABLE_FOOTER, $name);
            }

            return $result;
        }
    }

    public function import(string $dump)
    {
        $db = $this->db;
        $sql = '';
        $i = 0;
        while ($j = strpos($dump, "\n", $i)) {
            $s = substr($dump, $i, $j - $i);
            $i = $j + 1;

            if ($this->isComment($s)) {
                continue;
            }
            
            $sql .= $s . "\n";
            if ($s[strlen($s) - 1] != ';') {
                continue;
            }
            
            $db->exec($sql);
            $sql = '';
        }
        
        $s = substr($dump, $i);
        if (! $this->isComment($s)) {
            $sql .= $s;
        }

        if ($sql != '') {
            $db->exec($sql);
        }
    }

    private function isComment(string $s): bool
    {
        if (strlen($s) <= 2) {
            return true;
        }
        
        $s2 = substr($s, 0, 2);
        return ($s2 === '/*') || ($s2 === '--') || ($s2[0] === '#');
    }
}
