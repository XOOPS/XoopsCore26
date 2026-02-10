<?php
require_once __DIR__.'/../../../../init_new.php';

use Xoops\Core\Session\Fingerprint;

class FingerprintTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Fingerprint
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new Fingerprint;
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
        $this->assertInstanceOf('\Xoops\Core\Session\Fingerprint', $this->object);
        $this->assertInstanceOf('\Xoops\Core\Session\FingerprintInterface', $this->object);
    }

    public function testCheckSessionPrint()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
