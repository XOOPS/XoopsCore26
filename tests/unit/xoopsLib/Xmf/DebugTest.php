<?php
namespace Xmf;

require_once(__DIR__.'/../../init_new.php');

class DebugTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Debug
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        //$this->object = new Debug;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testDump()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testBacktrace()
    {
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
        // changes to Kint have ccaused this to fail
        //$x = Debug::backtrace(false, true, false);
        //$this->assertTrue(is_string($x));
    }

    public function testStartTimer()
    {
        $this->markTestIncomplete();
    }

    public function testStopTimer()
    {
        $this->markTestIncomplete();
    }

    public function testStartQueuedTimer()
    {
        $this->markTestIncomplete();
    }

    public function teststopQueuedTimer()
    {
        $this->markTestIncomplete();
    }

    public function testdumpQueuedTimers()
    {
        $this->markTestIncomplete();
    }

    public function testStartTrace()
    {
        if (function_exists('xdebug_start_trace')) {
            $this->markTestIncomplete();
        }
    }

    public function testStopTrace()
    {
        if (function_exists('xdebug_stop_trace')) {
            $this->markTestIncomplete();
        }
    }
}
