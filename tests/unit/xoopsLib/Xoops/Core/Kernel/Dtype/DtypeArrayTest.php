<?php
namespace Xoops\Core\Kernel\Dtype;

require_once __DIR__ . '/../../../../../init_new.php';

use Xoops\Core\Kernel\Dtype;
use Xoops\Core\Kernel\XoopsObject;

/**
 * Test XoopsObject with a Dtype::TYPE_ARRAY var
 */
class DtypeArrayObject extends XoopsObject
{
    public function __construct()
    {
        $this->initVar('arraytest', Dtype::TYPE_ARRAY);
    }
}

class DtypeArrayTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DtypeArray
     */
    protected $object;

    /**
     * @var DtypeArrayObject
     */
    protected $xObject;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new DtypeArray;
        $this->xObject = new DtypeArrayObject();
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
        $this->assertInstanceOf('\Xoops\Core\Kernel\Dtype\DtypeArray', $this->object);
    }

    public function testGetVarCleanVar()
    {
        $testArray = [
            'dog' => 'Spot',
            'girl' => 'Jane',
            'Boy' => 'Dick',
            'see' => 'run',
            "I'm a problem" => 'Not "really.',
        ];
        $key = 'arraytest';

        $this->xObject[$key] = $testArray;
        $this->xObject[$key] = $this->object->cleanVar($this->xObject, $key);
        //var_dump($this->xObject->vars[$key]['value']);
        $value = $this->xObject[$key];
        $this->assertTrue(is_array($value));
        $this->assertEquals('Spot', $value['dog']);
        $this->assertEquals('run', $value['see']);

        $value = $this->xObject->getVar($key, Dtype::FORMAT_SHOW);
        $this->assertTrue(is_array($value));
        //var_dump($value);

        $value = $this->xObject->getVar($key, Dtype::FORMAT_NONE);
        $this->assertTrue(is_string($value));
        //var_dump($value);
        $this->assertEquals("a:5:{s:", substr($value, 0, 7));
    }
}
