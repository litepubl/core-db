<?php

namespace LitePubl\Core\DB;

interface ErrorStrategyInterface
{
    public function error(string $message, string $sql);
    public function warning(string $message, string $sql);
}
