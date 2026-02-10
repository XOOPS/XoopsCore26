<?php
namespace Xoops\Core\Theme;

require_once __DIR__.'/../../../../init_new.php';

class AdminFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AdminFactory
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new AdminFactory;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testCreateInstance()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
