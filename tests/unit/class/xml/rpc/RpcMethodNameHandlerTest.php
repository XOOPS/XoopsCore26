<?php
require_once(__DIR__.'/../../../init_new.php');

class RpcMethodNameHandlerTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = 'RpcMethodNameHandler';
    protected $object = null;
    
    public function setUp(): void
    {
        $this->object = new $this->myclass();
    }
    
    public function test___construct()
    {
        $instance = $this->object;
        $this->assertInstanceof('XmlTagHandler', $instance);
    }

    public function test_getName()
    {
        $instance = $this->object;
        
        $name = $instance->getName();
        $this->assertSame('methodName', $name);
    }

    public function test_handleCharacterData()
    {
        $instance = $this->object;
        
        $input = 'input';
        $parser = new XoopsXmlRpcParser($input);
        $data = 'something';
        $instance->handleCharacterData($parser, $data);
        $this->assertSame($data, $parser->getMethodName());
    }
}
