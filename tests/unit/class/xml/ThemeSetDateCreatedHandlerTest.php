<?php
require_once(__DIR__.'/../../init_new.php');
require_once XOOPS_TU_ROOT_PATH . '/class/xml/themesetparser.php';

class ThemeSetDateCreatedHandlerTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = 'ThemeSetDateCreatedHandler';
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
        $this->assertSame('dateCreated', $name);
    }
    
    public function test_handleCharacterData()
    {
        $instance = $this->object;
        
        $input = 'input';
        $parser = new XoopsThemeSetParser($input);
        $parser->tags = array('themeset','themeset');
        $data = 'data';
        $instance->handleCharacterData($parser, $data);
        $this->assertSame($data, $parser->getThemeSetData('date'));
        
        $input = 'input';
        $parser = new XoopsThemeSetParser($input);
        $parser->tags = array('dummy','dummy');
        $data = 'data';
        $instance->handleCharacterData($parser, $data);
        $this->assertSame(false, $parser->getThemeSetData('date'));
    }
}
