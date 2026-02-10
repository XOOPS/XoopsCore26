<?php
namespace Xoops\Core\Kernel\Dtype;

require_once __DIR__ . '/../../../../../init_new.php';

use Xoops\Core\Kernel\Dtype;
use Xoops\Core\Kernel\XoopsObject;

/**
 * Test XoopsObject with a Dtype::TYPE_JSON var
 */
class DtypeJsonObject extends XoopsObject
{
    public function __construct()
    {
        $this->initVar('jsontest', Dtype::TYPE_JSON);
    }
}

class DtypeJsonTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DtypeJson
     */
    protected $object;

    /**
     * @var DtypeJsonObject
     */
    protected $xObject;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new DtypeJson;
        $this->xObject = new DtypeJsonObject();
    }

    public function testContracts()
    {
        $this->assertInstanceOf('\Xoops\Core\Kernel\Dtype\DtypeAbstract', $this->object);
        $this->assertInstanceOf('\Xoops\Core\Kernel\Dtype\DtypeJson', $this->object);
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
        $testArray = ['dog' => 'Spot', 'girl' => 'Jane', 'Boy' => 'Dick', 'see' => 'run'];
        $key = 'jsontest';

        $this->xObject[$key] = $testArray;
        $this->xObject[$key] = $this->object->cleanVar($this->xObject, $key);
        //var_dump($this->xObject->vars[$key]['value']);
        $value = $this->xObject[$key];
        $this->assertTrue(is_array($value));
        $this->assertEquals('Spot', $value['dog']);
        $this->assertEquals('run', $value['see']);

        $value = $this->xObject->getVar($key, Dtype::FORMAT_SHOW);
        $this->assertTrue(is_array($value));

        $value = $this->xObject->getVar($key, Dtype::FORMAT_NONE);
        $this->assertTrue(is_string($value));
        $value = json_decode($value);
        $this->assertInstanceOf('\stdClass', $value);
        $this->assertEquals('Spot', $value->dog);
        $this->assertEquals('run', $value->see);

        unset($this->xObject[$key]);
        $this->xObject[$key] = $this->object->cleanVar($this->xObject, $key);
        $value = $this->xObject[$key];
        $this->assertNull($value);

        $this->xObject[$key] = 'string';
        $this->xObject[$key] = $this->object->cleanVar($this->xObject, $key);
        $value = $this->xObject[$key];
        $this->assertEquals('string', $value);

        $this->xObject[$key] = json_decode(json_encode($testArray));
        $this->xObject[$key] = $this->object->cleanVar($this->xObject, $key);
        $value = $this->xObject[$key];
        $this->assertTrue(is_array($value));
        $this->assertEquals('Spot', $value['dog']);
        $this->assertEquals('run', $value['see']);

        $value = json_encode($testArray);
        $this->xObject[$key] = $value;
        $this->xObject[$key] = $this->object->cleanVar($this->xObject, $key);
        $actualValue = $this->xObject->getVar($key, Dtype::FORMAT_NONE);
        $this->assertSame($value, $actualValue);
    }
}
