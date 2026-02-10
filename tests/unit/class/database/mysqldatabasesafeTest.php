<?php
require_once(__DIR__.'/../../init_new.php');

class XoopsMySQLDatabaseSafeTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = 'XoopsMySQLDatabaseSafe';

    public function setUp(): void
    {
    }

    public function test___construct()
    {
        $instance = new $this->myclass();
        $this->assertInstanceOf('\XoopsMySQLDatabaseSafe', $instance);
        $this->assertInstanceOf('\XoopsMySQLDatabase', $instance);
        $this->assertInstanceOf('\XoopsDatabase', $instance);
    }
}
