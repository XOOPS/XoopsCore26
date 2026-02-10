<?php
require_once(__DIR__.'/../../../init_new.php');

class XoopsXmlRpcDocumentTestInstance extends XoopsXmlRpcDocument
{
    public function render()
    {
    }
    public function getTag()
    {
        return $this->_tags;
    }
}

class XoopsXmlRpcDocumentTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = 'XoopsXmlRpcDocumentTestInstance';
    protected $object = null;
    
    public function setUp(): void
    {
        $input = 'input';
        $this->object = new $this->myclass($input);
    }
    
    public function test___construct()
    {
        $instance = $this->object;
        $this->assertInstanceof($this->myclass, $instance);
    }
    
    public function test_add()
    {
        $instance = $this->object;
        
        $input = 'input';
        $object = new XoopsXmlRpcFault($input);
        $instance->add($object);
        $x = $instance->getTag();
        $this->assertSame($object, $x[0]);
    }
}
