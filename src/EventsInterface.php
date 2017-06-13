<?php

namespace LitePubl\Core\DB;

interface EventsInterface
{
    public function onQuery(string $sql);
    public function onAfterQuery();
    public function onException(Exception $e);
}
