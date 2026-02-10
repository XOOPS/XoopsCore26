<?php
namespace Xoops\Core\Theme;

require_once __DIR__.'/../../../../init_new.php';

class PluginAbstractTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PluginAbstract
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = $this->getMockBuilder('\Xoops\Core\Theme\PluginAbstract')
            ->onlyMethods(['xoInit'])
            ->getMock();
        $this->reflectedObject = new \ReflectionClass('\Xoops\Core\Theme\PluginAbstract');
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
        $this->assertTrue($this->reflectedObject->isAbstract());
        $this->assertTrue($this->reflectedObject->hasMethod('xoInit'));
    }
}
