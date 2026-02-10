<?php
require_once __DIR__.'/../../../../init_new.php';

use Xoops\Core\Cache\DriverList;

class DriverListTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DriverList
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        //$this->object = new DriverList;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testGetDriverClass()
    {
        $name1 = 'FileSystem';
        $class1 = DriverList::getDriverClass($name1);

        $name2 = 'filesystem';
        $class2 = DriverList::getDriverClass($name2);

        $this->assertSame($class1, $class2);
    }
}
