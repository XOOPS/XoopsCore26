<?php
require_once(__DIR__.'/../../../init_new.php');

class XoopsXmlRpcBooleanTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = 'XoopsXmlRpcBoolean';

    public function test___construct()
    {
        $value = 1;
        $x = new $this->myclass($value);
        $this->assertInstanceof($this->myclass, $x);
        $this->assertInstanceof('XoopsXmlRpcTag', $x);
    }

    public function test_render()
    {
        $value = 1;
        $instance = new $this->myclass($value);

        $value = $instance->render();
        $this->assertSame('<value><boolean>1</boolean></value>', $value);

        $instance = new $this->myclass(null);

        $value = $instance->render();
        $this->assertSame('<value><boolean>0</boolean></value>', $value);
    }
}
