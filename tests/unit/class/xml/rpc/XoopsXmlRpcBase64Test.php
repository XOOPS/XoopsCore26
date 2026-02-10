<?php
require_once(__DIR__.'/../../../init_new.php');

require_once(XOOPS_ROOT_PATH.'/class/xml/rpc/xmlrpcparser.php');

class XoopsXmlRpcBase64Test extends \PHPUnit\Framework\TestCase
{
    protected $myclass = 'XoopsXmlRpcBase64';
    
    public function test___construct()
    {
        $value = 'value';
        $x = new $this->myclass($value);
        $this->assertInstanceof($this->myclass, $x);
        $this->assertInstanceof('XoopsXmlRpcTag', $x);
    }

    public function test_render()
    {
        $value = 'value';
        $instance = new $this->myclass($value);
        
        $result = $instance->render();
        $this->assertSame('<value><base64>'.base64_encode($value).'</base64></value>', $result);
    }
}
