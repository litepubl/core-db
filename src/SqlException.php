<?php

namespace LitePubl\Core\DB;

class SqlException extends \UnexpectedValueException
{
    protected $sql;

    public function __construct(string $message, string $sql, \Throwable $previous = null)
    {
        $this->sql = $sql;

        parent::__construct($message, 0, $previous);
    }

    public function getSql(): string
    {
        return $this->sql;
    }
}
