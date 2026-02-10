<?php
namespace Xoops\Form;

require_once(__DIR__.'/../../../init_new.php');

class TabTrayTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TabTray
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new TabTray('Caption', 'name');
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
        $text = new Text('Caption', 'name', 10, 20, 'value', 'placeholder');
        $this->object->addElement($text);
        $value = $this->object->render();
        $this->assertTrue(is_string($value));
        $this->assertTrue(false !== strpos($value, 'id="tabs_name"'));
        $this->assertTrue(false !== strpos($value, 'type="text"'));
        $this->assertTrue(false !== strpos($value, 'name="name"'));
        $this->assertTrue(false !== strpos($value, 'size="10"'));
        $this->assertTrue(false !== strpos($value, 'maxlength="20"'));
        $this->assertTrue(false !== strpos($value, 'placeholder="placeholder"'));
        $this->assertTrue(false !== strpos($value, 'title="Caption"'));
        $this->assertTrue(false !== strpos($value, 'id="name"'));
        $this->assertTrue(false !== strpos($value, 'value="value"'));
    }
}
