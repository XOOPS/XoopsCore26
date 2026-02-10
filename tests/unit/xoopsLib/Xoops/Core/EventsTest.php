<?php
require_once(__DIR__.'/../../../init_new.php');

use Xoops\Core\Events;

class EventsTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = '\Xoops\Core\Events';
    public $dummy_result = null;

    /**
     * @var Class
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $class = $this->myclass;
        $this->object = $class::getInstance();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function test_getInstance()
    {
        $class = $this->myclass;
        $instance = $class::getInstance();
        $this->assertInstanceOf($class, $instance);

        $instance1 = $class::getInstance();
        $this->assertSame($instance1, $instance);
    }

    public function test_initializeListeners()
    {
        $instance = $this->object;

        $result = $instance->getEvents();
        $this->assertTrue(is_array($result));
    }

    public function dummy_callback($arg)
    {
        $this->dummy_result = $arg;
    }

    public function test_triggerEvent()
    {
        $instance = $this->object;

        $callback = array($this,'dummy_callback');
        $name = 'dummy.listener';
        $instance->addListener($name, $callback);

        $instance->triggerEvent('dummy.listener', array(1,2));
        $this->assertSame(array(1,2), $this->dummy_result);
    }

    public function test_hasListeners()
    {
        $instance = $this->object;

        $result = $instance->hasListeners('listener_doesnt_exist');
        $this->assertFalse($result);

        $result = $instance->hasListeners('core.header.checkcache');
        $this->assertTrue($result);
    }
}
