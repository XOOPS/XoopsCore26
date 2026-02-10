<?php
require_once(__DIR__.'/../../../../init_new.php');

use Xoops\Core\Database\Factory;

class FactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Factory
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        // $this->object = new Factory;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testGetConnection()
    {
        $result = Factory::getConnection();
        $this->assertInstanceOf('\Xoops\Core\Database\Connection', $result);
    }
}
