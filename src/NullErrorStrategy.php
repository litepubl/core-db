<?php

namespace LitePubl\Core\DB;

class NullErrorStrategy implements ErrorStrategyInterface
{
    public function error(string $message, string $sql)
    {
    }

    public function warning(string $message, string $sql)
    {
    }
}
