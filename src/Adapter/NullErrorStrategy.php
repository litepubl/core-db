<?php

namespace LitePubl\Core\DB\Adapter;

class NullErrorStrategy implements ErrorStrategyInterface
{
    public function error(string $message, string $sql)
    {
    }

    public function warning(string $message, string $sql)
    {
    }
}
