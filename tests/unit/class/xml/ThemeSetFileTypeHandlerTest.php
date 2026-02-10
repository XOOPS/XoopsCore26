<?php
require_once(__DIR__.'/../../init_new.php');
require_once XOOPS_TU_ROOT_PATH . '/class/xml/themesetparser.php';

class ThemeSetFileTypeHandlerTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = 'ThemeSetFileTypeHandler';
    protected $object = null;
    
    public function setUp(): void
    {
        $input = 'input';
        $this->object = new $this->myclass($input);
    }

    public function test___construct()
    {
        $instance = $this->object;
        $this->assertInstanceOf('XmlTagHandler', $instance);
    }

    public function test_getName()
    {
        $instance = $this->object;
        
        $name = $instance->getName();
        $this->assertSame('fileType', $name);
    }

    public function test_handleCharacterData()
    {
        $instance = $this->object;
        
        $input = 'input';
        $parser = new XoopsThemeSetParser($input);
        $parser->tags = array('template','template');
        $data = 'something';
        $instance->handleCharacterData($parser, $data);
        $this->assertSame($data, $parser->getTempArr('type'));

        $input = 'input';
        $parser = new XoopsThemeSetParser($input);
        $parser->tags = array('dummy','dummy');
        $data = 'something';
        $instance->handleCharacterData($parser, $data);
        $this->assertSame(false, $parser->getTempArr('type'));
    }
}
