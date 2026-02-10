<?php
namespace Xoops\Core\Kernel\Dtype;

require_once __DIR__ . '/../../../../../init_new.php';

use Xoops\Core\Kernel\Dtype;
use Xoops\Core\Kernel\XoopsObject;

/**
 * Test XoopsObject with a Dtype::TYPE_DATETIME var
 */
class DtypeDateTimeObject extends XoopsObject
{
    public function __construct()
    {
        $this->initVar('datetime_test', Dtype::TYPE_DATETIME);
    }
}

class DtypeDateTimeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DtypeDateTime
     */
    protected $object;

    /**
     * @var DtypeDateTimeObject
     */
    protected $xObject;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new DtypeDateTime();
        $this->xObject = new DtypeDateTimeObject();
    }

    public function testContracts()
    {
        $this->assertInstanceOf('\Xoops\Core\Kernel\Dtype\DtypeAbstract', $this->object);
        $this->assertInstanceOf('\Xoops\Core\Kernel\Dtype\DtypeDateTime', $this->object);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testGetVarCleanVar()
    {
        $testValue = time();
        $key = 'datetime_test';

        $this->xObject[$key] = $testValue;
        $this->xObject[$key] = $this->object->cleanVar($this->xObject, $key);
        $value = $this->xObject->getVar($key, Dtype::FORMAT_NONE);
        $this->assertEquals($testValue, $value);

        $value1 = $this->xObject->getVar($key, Dtype::FORMAT_SHOW);
        $this->assertInstanceOf('\DateTime', $value1);
        $this->assertEquals($testValue, $value1->getTimestamp());
        $value2 = $this->xObject[$key];
        $this->assertInstanceOf('\DateTime', $value2);
        $this->assertEquals($testValue, $value2->getTimestamp());
        $this->assertNotSame($value1, $value2);
    }
}
