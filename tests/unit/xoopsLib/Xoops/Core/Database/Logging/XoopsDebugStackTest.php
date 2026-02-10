<?php
require_once(__DIR__.'/../../../../../init_new.php');

class XoopsDebugStackTest extends \PHPUnit\Framework\TestCase
{
    protected $myClass = 'Xoops\Core\Database\Logging\XoopsQueryLogger';

    public function test___construct()
    {
        $instance = new $this->myClass();
        $this->assertInstanceOf($this->myClass, $instance);
    }

    public function test_startAndStopQuery()
    {
        $instance = new $this->myClass();
        $this->assertInstanceOf($this->myClass, $instance);

        $instance->startQuery('SELECT 1', [], []);
        $this->assertSame(1, $instance->currentQuery);
        $this->assertArrayHasKey(1, $instance->queries);
        $this->assertSame('SELECT 1', $instance->queries[1]['sql']);

        $instance->stopQuery();
        $this->assertGreaterThan(0, $instance->queries[1]['executionMS']);
    }

    public function test_enabled()
    {
        $instance = new $this->myClass();
        $instance->enabled = false;
        $instance->startQuery('SELECT 1');
        $this->assertSame(0, $instance->currentQuery);

        $instance->enabled = true;
        $instance->startQuery('SELECT 2');
        $this->assertSame(1, $instance->currentQuery);
    }
}
