<?php
namespace Xoops\Form;

require_once(__DIR__.'/../../../init_new.php');

class SelectLocaleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SelectLocale
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new SelectLocale('Caption', 'name');
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
        $this->assertTrue(false !== strpos($value, '<select'));
        $this->assertTrue(false !== strpos($value, 'name="name"'));
        $this->assertTrue(false !== strpos($value, 'size="1"'));
        $this->assertTrue(false !== strpos($value, 'title="Caption"'));
        $this->assertTrue(false !== strpos($value, 'id="name"'));

        $this->assertTrue(false !== strpos($value, '<option'));
        $this->assertTrue(false !== strpos($value, 'value="en_US"'));
        $this->assertTrue(false !== strpos($value, '</option>'));
    }

    public function test__construct()
    {
        $oldWay = new SelectLocale('mycaption', 'myname', 'fr_FR');
        $newWay = new SelectLocale([
            'caption' => 'mycaption',
            'name' => 'myname',
            'value' => 'fr_FR',
        ]);
        $this->assertEquals($oldWay->render(), $newWay->render());
    }
}
