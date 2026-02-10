<?php
require_once(__DIR__.'/../../../init_new.php');

class RpcIntHandlerTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = 'RpcIntHandler';
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
        $this->assertSame(array('int', 'i4'), $name);
    }

    public function test_handleCharacterData()
    {
        $instance = $this->object;
        
        $input = 'input';
        $parser = new XoopsXmlRpcParser($input);
        $data = 71;
        $instance->handleCharacterData($parser, $data);
        $this->assertSame($data, $parser->getTempValue());
    }
}
