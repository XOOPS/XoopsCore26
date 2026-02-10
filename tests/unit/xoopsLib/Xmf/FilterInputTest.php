<?php
namespace Xmf\Test;

use PHPUnit\Framework\Attributes\DataProvider;
use Xmf\FilterInput;

class FilterInputTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FilterInput
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = FilterInput::getInstance();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testGetInstance()
    {
        $this->assertInstanceOf('\Xmf\FilterInput', $this->object);

        $instance = FilterInput::getInstance();
        $this->assertSame($instance, $this->object);

        $instance = FilterInput::getInstance(array(), array(), 0, 0, 0);
        $this->assertNotSame($instance, $this->object);
    }

    public function testProcess()
    {
        $input = 'Lorem ipsum </i><script>alert();</script>';
        $expected = 'Lorem ipsum alert();';
        $this->assertEquals($expected, $this->object->process($input));

        $input = 'Lorem ipsum';
        $this->assertEquals($input, $this->object->process($input));
    }

    public function testClean()
    {
        $input = 'Lorem ipsum </i><script>alert();</script>';
        $expected = 'Lorem ipsum alert();';
        $this->assertEquals($expected, FilterInput::clean($input, 'string'));

        $input = 'Lorem ipsum &#x3C;&#x73;&#x63;&#x72;&#x69;&#x70;&#x74;&#x3E;&#x61;&#x6C;&#x65;&#x72;&#x74;&#x28;&#x29;&#x3B;&#x3C;&#x2F;&#x73;&#x63;&#x72;&#x69;&#x70;&#x74;&#x3E;';
        $expected = 'Lorem ipsum alert();';
        $this->assertEquals($expected, FilterInput::clean($input, 'string'), FilterInput::clean($input, 'string'));

        $input = 'Lorem ipsum';
        $expected = $input;
        $this->assertEquals($expected, FilterInput::clean($input, 'string'));
    }

    public function testCleanVarDefault()
    {
        $filter = FilterInput::getInstance();
        $safeTest = '<p>This is a <em>simple</em> test.</p>';
        $this->assertEquals('This is a simple test.', $filter->cleanVar($safeTest));
    }

    public function testCleanVarFilter()
    {
        $filter = FilterInput::getInstance(array(), array(), 1, 1);

        $safeTest = '<p>This is a <em>simple</em> test.</p>';
        $this->assertEquals($safeTest, $filter->cleanVar($safeTest));
    }

    public function testCleanVarFilterXss()
    {
        $filter = FilterInput::getInstance(array(), array(), 1, 1);

        $xssTest = '<p>This is a <em>xss</em> <script>alert();</script> test.</p>';
        $xssTestExpect = '<p>This is a <em>xss</em> alert(); test.</p>';
        $this->assertEquals($xssTestExpect, $filter->cleanVar($xssTest));
    }

    public static function getTestForCleanVarType()
    {
        return array(
            array('100', 'int', 100),
            array('100', 'INTEGER', 100),
            array('55.1', 'FLOAT', 55.1),
            array('55.1', 'DOUBLE', 55.1),
            array('1', 'BOOL', true),
            array('0', 'BOOLEAN', false),
            array('Value', 'WORD', 'Value'),
            array('Alpha99', 'ALPHANUM', 'Alpha99'),
            array('Alpha99', 'ALNUM', 'Alpha99'),
            array('value', 'ARRAY', array('value')),
//          ['value', 'type', 'expected'],
        );
    }

    #[DataProvider('getTestForCleanVarType')]
    public function testCleanVarTypes($value, $type, $expected)
    {
        $this->assertSame($expected, $this->object->cleanVar($value, $type));
    }
}
