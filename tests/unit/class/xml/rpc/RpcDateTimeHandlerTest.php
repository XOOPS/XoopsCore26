<?php
require_once(__DIR__.'/../../../init_new.php');

class RpcDateTimeHandlerTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = 'RpcDateTimeHandler';
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
        $this->assertSame('dateTime.iso8601', $name);
    }

    public function test_handleCharacterData()
    {
        $instance = $this->object;

        $input = 'input';
        $parser = new XoopsXmlRpcParser($input);
        $data = 'not time';
        $instance->handleCharacterData($parser, $data);
        $this->assertTrue(is_int($parser->getTempValue()));

        $input = 'input';
        $parser = new XoopsXmlRpcParser($input);
        $data = '1900 01 30T01:30:01';
        $instance->handleCharacterData($parser, $data);
        $this->assertTrue(is_int($parser->getTempValue()));
    }
}
