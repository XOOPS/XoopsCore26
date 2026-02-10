<?php
namespace Xoops\Core\Kernel\Dtype;

require_once __DIR__ . '/../../../../../init_new.php';

use Xoops\Core\Kernel\Dtype;
use Xoops\Core\Kernel\XoopsObject;
use Money\Money;
use Money\Currency;

/**
 * Test XoopsObject with a Dtype::TYPE_MONEY var
 */
class DtypeMoneyObject extends XoopsObject
{
    public function __construct()
    {
        $this->initVar('money_test', Dtype::TYPE_MONEY);
    }
}

class DtypeMoneyTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DtypeMoney
     */
    protected $object;

    /**
     * @var DtypeMoneyObject
     */
    protected $xObject;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new DtypeMoney();
        $this->xObject = new DtypeMoneyObject();
    }

    public function testContracts()
    {
        $this->assertInstanceOf('\Xoops\Core\Kernel\Dtype\DtypeAbstract', $this->object);
        $this->assertInstanceOf('\Xoops\Core\Kernel\Dtype\DtypeMoney', $this->object);
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
        $testValue = new Money(10000, new Currency('USD'));
        $key = 'money_test';
        $this->xObject[$key] = $testValue;
        $this->xObject[$key] = $this->object->cleanVar($this->xObject, $key);

        $value = $this->xObject->getVar($key, Dtype::FORMAT_NONE);
        $this->assertInstanceOf('\Money\Money', $value);
        $this->assertEquals($testValue->getAmount(), $value->getAmount());
        $this->assertTrue($testValue->getCurrency()->equals($value->getCurrency()));
        $this->assertNotSame($value, $testValue);

        $value2 = $this->xObject->getVar($key, Dtype::FORMAT_SHOW);
        $this->assertInstanceOf('\Money\Money', $value2);
        $this->assertEquals($testValue->getAmount(), $value2->getAmount());
        $this->assertTrue($testValue->getCurrency()->equals($value2->getCurrency()));
        $this->assertNotSame($value, $value2);
    }

    public function testStoredRepresentation()
    {
        $testValue = new Money(30000, new Currency('EUR'));
        $key = 'money_test';
        $this->xObject[$key] = $testValue;
        $this->xObject->cleanVars();
        $value = $this->xObject->cleanVars[$key];
        $this->assertTrue(is_string($value));
        $decode = json_decode($value, true, 2, JSON_BIGINT_AS_STRING);
        $this->assertTrue(is_array($decode));
        $this->assertSame((string) $decode['amount'], '30000');
        $this->assertSame($decode['currency'], 'EUR');
    }
}
