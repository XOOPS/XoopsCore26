<?php
namespace Xoops\Form;

require_once(__DIR__.'/../../../init_new.php');

class CheckboxTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Checkbox
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new Checkbox('Caption', 'name');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testGetOptions()
    {
        $this->object->addOption('AO_value', 'AO_name');
        $this->object->addOption('AO_noname');

        $options = array('AOA_value' => 'AOA_name', 'AOA_noname' => '');
        $this->object->addOptionArray($options);

        $options = $this->object->getOptions();
        $this->assertTrue(is_array($options));
        $this->assertSame('AO_name', $options['AO_value']);
        $this->assertSame('AO_noname', $options['AO_noname']);
        $this->assertSame('AOA_name', $options['AOA_value']);
        $this->assertSame('AOA_noname', $options['AOA_noname']);
    }

    public function testRender()
    {
        $value = $this->object->render();
        $this->assertTrue(is_string($value));
    }

    public function testRenderValidationJS()
    {
        $value = $this->object->renderValidationJS();
        $this->assertTrue(is_string($value));
    }

    public function test__construct()
    {
        $oldWay = new Checkbox('mycaption', 'myname', 'opt1');
        $oldWay->addOption('opt1', 'optname1');
        $oldWay->addOption('opt2', 'optname2');
        $oldWay->setRequired();
        $newWay = new Checkbox([
            'caption' => 'mycaption',
            'name' => 'myname',
            'value' => 'opt1',
            'required' => null,
            'option' => [
                'opt1' => 'optname1',
                'opt2' => 'optname2',
            ]
        ]);
        $this->assertEquals($oldWay->render(), $newWay->render());
        $this->assertEquals($oldWay->renderValidationJS(), $newWay->renderValidationJS());
    }
}
