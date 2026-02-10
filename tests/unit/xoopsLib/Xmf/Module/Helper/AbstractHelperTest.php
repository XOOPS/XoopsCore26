<?php
namespace Xmf\Module\Helper;

require_once(__DIR__.'/../../../../init_new.php');

class AbstractHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AbstractHelper
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        //$this->object = new \Xmf\Module\Helper\AbstractHelper;
        $this->object = $this->getMockBuilder('Xmf\Module\Helper\AbstractHelper')
            ->onlyMethods(['init'])
            ->getMock();
        //$this->object->expects($this->any())
        //    ->method('getDefaultParams')
        //    ->will($this->returnValue(array()));
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testSetDebug()
    {
        $this->assertTrue(method_exists($this->object, 'setDebug'));
        $ref = new \ReflectionProperty($this->object, 'debug');
        $ref->setAccessible(true);
        $this->object->setDebug(true);
        $this->assertTrue($ref->getValue($this->object));
        $this->object->setDebug(false);
        $this->assertFalse($ref->getValue($this->object));
    }

    public function testAddLog()
    {
        $this->assertTrue(method_exists($this->object, 'addLog'));
        $this->object->addLog('message to send to bitbucket');
    }
}
