<?php
require_once(__DIR__.'/../../../init_new.php');

class RpcNameHandlerTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = 'RpcNameHandler';
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
        $this->assertSame('name', $name);
    }

    public function test_handleCharacterData()
    {
        $instance = $this->object;

        $input = 'input';
        $parser = new XoopsXmlRpcParser($input);
        $parser->tags = array('member','member');
        $value = '71';
        $instance->handleCharacterData($parser, $value);
        $this->assertSame($value, $parser->getTempName());

        $input = 'input';
        $parser = new XoopsXmlRpcParser($input);
        $parser->tags = array('dummy','dummy');
        $value = '71';
        $instance->handleCharacterData($parser, $value);
        $this->assertSame(null, $parser->getTempName());
    }
}
