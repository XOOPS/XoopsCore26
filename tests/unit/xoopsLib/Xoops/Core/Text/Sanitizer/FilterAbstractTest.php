<?php
namespace Xoops\Core\Text\Sanitizer;

use Xoops\Core\Text\Sanitizer;

require_once __DIR__.'/../../../../../init_new.php';

class FilterAbstractTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FilterAbstract
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
        $ts = Sanitizer::getInstance();
        $this->object = $this->getMockBuilder('\Xoops\Core\Text\Sanitizer\FilterAbstract')->setConstructorArgs([$ts])->onlyMethods(['applyFilter'])->getMock();
        $this->reflectedObject = new \ReflectionClass('\Xoops\Core\Text\Sanitizer\FilterAbstract');
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
        $this->assertTrue($this->reflectedObject->hasMethod('applyFilter'));
    }

}
