<?php
namespace Xoops\Form;

require_once(__DIR__.'/../../../init_new.php');

class FileTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var File
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new File('Caption', 'name');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testRender()
    {
        $value = $this->object->render();
        $this->assertTrue(is_string($value));
    }

    public function test__construct()
    {
        $oldWay = new File('mycaption', 'myname');
        $newWay = new File(['caption' => 'mycaption', 'name' => 'myname',]);
        $this->assertEquals($oldWay->render(), $newWay->render());
    }
}
