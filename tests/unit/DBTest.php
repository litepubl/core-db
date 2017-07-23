<?php

namespace LitePubl\Tests\DB;

use LitePubl\Core\DB\DB;
use LitePubl\Core\DB\DBInterface;
use LitePubl\Core\DB\EventsInterface;
use LitePubl\Core\DB\Adapter\AdapterInterface;
use Prophecy\Argument;

class DBTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    protected $prefix = 'site_';

    public function testMe()
    {
                $adapter = $this->prophesize(AdapterInterface::class);
                $events = $this->prophesize(EventsInterface::class);
        $DB = new DB($adapter->reveal(), $events->reveal(), $this->prefix);
        $this->assertInstanceOf(DB::class, $DB);
        $this->assertInstanceOf(DBInterface::class, $DB);
        $this->assertInstanceOf(AdapterInterface::class, $DB->getAdapter());
        $this->assertInstanceOf(EventsInterface::class, $DB->getEvents());

        $this->assertTrue($adapter->reveal() === $DB->getAdapter());

/*
        $factory->has(Argument::type('string'))->willReturn(false);
        $this->tester->expectException(NotFoundExceptionInterface ::class, function () use ($DB) {
                $DB->get(Unknown::class);
        });
*/
    }
}
