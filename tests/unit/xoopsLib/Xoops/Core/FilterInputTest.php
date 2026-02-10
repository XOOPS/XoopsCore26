<?php
namespace Xoops\Core;

require_once(__DIR__.'/../../../init_new.php');

class FilterInputTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FilterInput
     */
    protected $object;

    protected $myclass = 'Xoops\Core\FilterInput';

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
        $class = $this->myclass;
        $this->assertInstanceOf($class, $this->object);

        $instance = $class::getInstance();
        $this->assertSame($instance, $this->object);
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

    public function testGather()
    {
        $specs = array(
            array('op','string'),
            array('ok', 'boolean', false, false),
            array('str', 'word', 'something', true, 5),
        );

        unset($_POST['op']);
        unset($_POST['ok']);
        $clean_input = FilterInput::gather('post', $specs, 'op');
        $this->assertFalse($clean_input);

        $_POST['op']='test';
        $clean_input = FilterInput::gather('post', $specs, 'op');
        $this->assertEquals('test', $clean_input['op']);
        $this->assertFalse($clean_input['ok']);
        $this->assertEquals('somet', $clean_input['str']);

        unset($_POST['op']);
        $_POST['ok']='1';
        $_POST['str'] = '  fred! ';
        $clean_input = FilterInput::gather('post', $specs);
        $this->assertEquals('', $clean_input['op']);
        $this->assertTrue($clean_input['ok']);
        $this->assertEquals('fred', $clean_input['str'], $clean_input['str']);

        unset($_POST['op']);
        unset($_POST['ok']);
        unset($_POST['str']);

    }
}
