<?php
namespace Xoops\Core\Text\Sanitizer;

require_once __DIR__.'/../../../../../init_new.php';

class SanitizerConfigurableTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SanitizerConfigurable
     */
    protected $object;

    /**
     * @var \ReflectionClass
     */
    protected $reflectedObject;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = $this->getMockBuilder('\Xoops\Core\Text\Sanitizer\SanitizerConfigurable')
            ->onlyMethods([])
            ->getMock();
        $this->reflectedObject = new \ReflectionClass('\Xoops\Core\Text\Sanitizer\SanitizerConfigurable');
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
        $this->assertTrue($this->reflectedObject->hasMethod('getDefaultConfig'));
        $this->assertTrue($this->reflectedObject->hasProperty('defaultConfiguration'));
    }

    public function testGetDefaultConfig()
    {
        $defaults = $this->object->getDefaultConfig();
        $this->assertTrue(is_array($defaults));
    }
}
