<?php
require_once(__DIR__.'/../../../init_new.php');

class XoopsXmlRpcRequestTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = 'XoopsXmlRpcRequest';
    protected $object = null;

    public function setUp(): void
    {
        $input = 'input';
        $this->object = new $this->myclass($input);
    }

    public function test___construct()
    {
        $instance = $this->object;
        $this->assertInstanceof('XoopsXmlRpcDocument', $instance);

        $this->assertSame('input', $instance->methodName);
    }

    public function test_render()
    {
        $instance = $this->object;

        $x = $instance->render();
        $this->assertTrue(is_string($x));
        $this->assertTrue(!empty($x));
    }
}
