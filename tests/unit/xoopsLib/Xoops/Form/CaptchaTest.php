<?php
namespace Xoops\Form;

require_once(__DIR__.'/../../../init_new.php');

class CaptchaTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Captcha
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new Captcha('Caption', 'name');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testSetConfig()
    {
        $value = $this->object->setConfig('dummy_name', 'dummy_value');
        $this->assertTrue($value);

        $handler = \XoopsCaptcha::getInstance();
        $configs = $handler->config;
        $this->assertTrue(is_array($configs));
        $this->assertSame('dummy_value', $configs['dummy_name']);
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
}
