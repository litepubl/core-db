<?php

namespace LitePubl\Core\DB;

class LogEvents implements EventsInterface
{
    const FORMAT = "%index%: %time%\n%sql%\n\n";
    const SUMMARY_FORMAT =         "maximum %time%\n%sql%\n%total% summary time\n%count% queries\n\n";

    protected $format;
    protected $summaryFormat;
    protected $log;
    protected $item;

    public function __construct(string $format = self::FORMAT, string $summaryFormat = self::SUMMARY_FORMAT)
    {
        $this->format = $format;
        $this->summaryFormat = $summaryFormat;
        $this->log = [];
    }

    public function onQuery(string $sql)
    {
        $this->item = [
                'sql' => $sql,
        'started' => microtime(true),
        ];
    }

    public function onAfterQuery()
    {
        $this->item['finished'] = microtime(true);
        $this->log[] = $this->item;
    }

    public function onException(Exception $e)
    {
        $this->afterQuery();
    }

    public function getLog(): array
    {
        return $this->log;
    }

    public function getFormated(): string
    {
        $result = '';
        $total = 0.0;
        $max = 0.0;
                $maxsql = '';
        foreach ($this->log as $i => $item) {
            $time = $item['finished'] - $item['started'];
            $total+= $time;
            if ($max < $time) {
                $max = $time;
                $maxsql = $item['sql'];
            }

            $result.= strtr($this->format, [
            '%sql%' => $item['sql'],
            '%time%' => $time,
            '%index%' => $i,
            ]);
        }

            $result.= strtr($this->summaryFormat, [
        '%sql%' => $maxSql,
        '%time' => $max,
        '%total%' => $total,
        '%count%' => count($this->log),
            ]);

        return $result;
    }
}
