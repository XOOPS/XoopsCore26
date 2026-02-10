<?php
namespace Xoops\Form;

require_once (__DIR__ .'/../../../init_new.php');

require_once(XOOPS_TU_ROOT_PATH . '/include/defines.php');

class SelectMatchOptionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SelectMatchOption
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new SelectMatchOption('Caption', 'name');
        //$this->markTestSkipped('side effects defines.php not included');
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
        $this->assertTrue(false !== strpos($value, 'value="0"'));
        $this->assertTrue(false !== strpos($value, '</option>'));
    }

    public function test__construct()
    {
        $oldWay = new SelectMatchOption('mycaption', 'myname', XOOPS_MATCH_START);
        $newWay = new SelectMatchOption([
            'caption' => 'mycaption',
            'name' => 'myname',
            'value' => XOOPS_MATCH_START,
        ]);
        $this->assertEquals($oldWay->render(), $newWay->render());
    }
}
