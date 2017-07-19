<?php
// Here you can initialize variables that will be available to your tests
use Codeception\Util\Autoload;

Autoload::addNamespace('LitePubl\Core\DB', __DIR__ . '/../../src');
Autoload::addNamespace('LitePubl\Tests\DB', __DIR__);
