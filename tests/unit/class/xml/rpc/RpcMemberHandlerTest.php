<?php
require_once(__DIR__.'/../../../init_new.php');

class RpcMemberHandlerTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = 'RpcMemberHandler';
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
        $this->assertSame('member', $name);
    }

    public function test_handleEndElement()
    {
        $instance = $this->object;

        $input = 'input';
        $parser = new XoopsXmlRpcParser($input);
        $workingLevelBefore = $parser->getWorkingLevel();
        $data = 'not time';
        $instance->handleBeginElement($parser, $data);
        $workingLevel = $parser->getWorkingLevel();
        $tempMember = $parser->getTempMember();
        $this->assertSame(array(), $tempMember);
        $this->assertNotSame($workingLevelBefore, $workingLevel);

        $instance->handleEndElement($parser, $data);
        $workingLevel = $parser->getWorkingLevel();
        $tempMember = $parser->getTempMember();
        $this->assertSame(null, $tempMember);
        $this->assertSame($workingLevelBefore, $workingLevel);
    }
}
