<?php

namespace LitePubl\Core\DB;

class NullEvents implements EventsInterface
{
    public function onQuery(string $sql)
    {
    }

    public function onAfterQuery()
    {
    }

    public function onException(Exception $e)
    {
    }
}
