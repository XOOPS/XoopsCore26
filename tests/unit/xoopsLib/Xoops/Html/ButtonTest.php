<?php
require_once(__DIR__.'/../../../init_new.php');

use Xoops\Html\Button;

class ButtonTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Img
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new Button([
            'type'  => 'submit',
            'class' => 'button1',
            'value' => 'sometext'
        ]);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function test__construct()
    {
        $object = new Button(array('type' => 'button'));
        $this->assertInstanceOf('\Xoops\Html\Attributes', $object);
        $this->assertEquals('button', $object->get('type'));
    }

    public function testRender()
    {
        $output = $this->object->render();
        $this->assertStringStartsWith('<button ', $output);
        $this->assertStringEndsWith('</button>', $output);
        $this->assertGreaterThanOrEqual(7, strpos($output, 'type="submit"'));
        $this->assertGreaterThanOrEqual(7, strpos($output, 'class="button1"'));
        $this->assertGreaterThanOrEqual(7, strpos($output, '>sometext<'));
    }
}
