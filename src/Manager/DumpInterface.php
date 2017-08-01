<?php

namespace LitePubl\Core\DB\Manager;

interface DumpInterface
{
    public function export(): string;
    public function exportTable(string $name): string;
    public function import(string $dump);
}
