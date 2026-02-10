<?php
require_once(__DIR__.'/../../../init_new.php');

class RpcBooleanHandlerTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = 'RpcBooleanHandler';
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
        $this->assertSame('boolean', $name);
    }

    public function test_handleCharacterData()
    {
        $instance = $this->object;
        
        $input = 'input';
        $parser = new XoopsXmlRpcParser($input);
        $data = true;
        $instance->handleCharacterData($parser, $data);
        $this->assertSame($data, $parser->getTempValue());
    }
}
