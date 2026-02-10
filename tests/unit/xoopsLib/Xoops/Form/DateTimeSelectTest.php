<?php
namespace Xoops\Form;

require_once(__DIR__.'/../../../init_new.php');

class DateTimeSelectTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DateTimeSelect
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new DateTimeSelect('Caption', 'name');
        \Xoops::getInstance()->setTheme(new \Xoops\Core\Theme\NullTheme);
        //$this->markTestSkipped('side effects');
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
        $oldWay = new DateTimeSelect('mycaption', 'myname');
        $newWay = new DateTimeSelect(['caption' => 'mycaption', 'name' => 'myname',]);
        $this->assertEquals($oldWay->render(), $newWay->render());
    }

    public function test_const()
    {
        $this->assertNotNull(DateTimeSelect::SHOW_BOTH);
        $this->assertNotNull(DateTimeSelect::SHOW_DATE);
        $this->assertNotNull(DateTimeSelect::SHOW_TIME);
    }
}
