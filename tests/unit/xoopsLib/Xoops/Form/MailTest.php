<?php
namespace Xoops\Form;

require_once(__DIR__.'/../../../init_new.php');

class MailTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Mail
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new Mail('Caption', 'name', 40, 180);
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
        $this->assertTrue(false !== strpos($value, '<input'));
        $this->assertTrue(false !== strpos($value, 'type="email"'));
    }
}
