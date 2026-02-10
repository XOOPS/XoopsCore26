<?php
namespace Xoops\Core\Kernel\Dtype;

require_once __DIR__ . '/../../../../../init_new.php';

use PHPUnit\Framework\Attributes\DataProvider;
use Xoops\Core\Kernel\Dtype;
use Xoops\Core\Kernel\XoopsObject;

/**
 * Test XoopsObject with a Dtype::TYPE_SHORT_TIME, TYPE_MEDIUM_TIME and TYPE_LONG_TIME vars
 */
class DtypeSimpleTimeObject extends XoopsObject
{
    public function __construct()
    {
        $this->initVar('stime_test', Dtype::TYPE_SHORT_TIME);
        $this->initVar('mtime_test', Dtype::TYPE_MEDIUM_TIME);
        $this->initVar('ltime_test', Dtype::TYPE_LONG_TIME);
    }
}

class DtypeSimpleTimeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DtypeSimpleTime
     */
    protected $object;

    /**
     * @var DtypeSimpleTimeObject
     */
    protected $xObject;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new DtypeSimpleTime();
        $this->xObject = new DtypeSimpleTimeObject();
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
        $this->assertInstanceOf('\Xoops\Core\Kernel\Dtype\DtypeAbstract', $this->object);
        $this->assertInstanceOf('\Xoops\Core\Kernel\Dtype\DtypeSimpleTime', $this->object);
    }

    #[DataProvider('provider')]
    public function testCleanVar($objectKey)
    {
        $testValue = time();

        $key = $objectKey;
        $this->xObject[$key] = $testValue;
        $this->xObject[$key] = $this->object->cleanVar($this->xObject, $key);
        $value = $this->xObject->getVar($key, Dtype::FORMAT_NONE);
        $this->assertEquals($testValue, $value);

        $this->xObject[$key] = date(DATE_RFC850, $testValue);
        $this->xObject[$key] = $this->object->cleanVar($this->xObject, $key);
        $value = $this->xObject->getVar($key, Dtype::FORMAT_NONE);
        $this->assertEquals($testValue, $value);
    }

    public static function provider()
    {
        return [
            ['stime_test'],
            ['mtime_test'],
            ['ltime_test'],
        ];
    }
}
