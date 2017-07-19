<?php

namespace LitePubl\Core\DB;

class ErrorStrategy implements ErrorStrategyInterface
{
    protected $logManager;

    public function __construct(LogManagerInterface $logManager)
    {
        $this->logManager = $logManager;
    }

    public function error(string $message, string $sql)
    {
        throw new SqlException($message, $sql);
    }

    public function warning(string $message, string $sql)
    {
        $this->logManager->getLogger()->warning($message, ['sql' => $sql]);
    }
}
