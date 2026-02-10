<?php
namespace Xoops\Form;

require_once(__DIR__.'/../../../init_new.php');

class GroupCheckboxTest extends \PHPUnit\Framework\TestCase
{
    use \DatabaseTestTrait;

    /**
     * @var GroupCheckbox
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->object = new GroupCheckbox('Caption', 'name');
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
}
