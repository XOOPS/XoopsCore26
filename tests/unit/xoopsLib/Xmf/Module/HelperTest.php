<?php
namespace Xmf\Module;

require_once(__DIR__.'/../../../init_new.php');

class HelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Helper
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {

        $this->object = Helper::getHelper();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testGetHelper()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
}
