<?php
namespace Xoops\Core\Exception;

require_once __DIR__ . '/../../../../init_new.php';

class InvalidHandlerSpecExceptionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var InvalidHandlerSpecException
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new InvalidHandlerSpecException;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testContracts()
    {
        $this->assertInstanceOf('\Xoops\Core\Exception\InvalidHandlerSpecException', $this->object);
        $this->assertInstanceOf('\InvalidArgumentException', $this->object);
    }

    public function testException()
    {
        $this->expectException('\Xoops\Core\Exception\InvalidHandlerSpecException');
        throw $this->object;
    }
}
