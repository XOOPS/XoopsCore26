<?php
require_once(__DIR__.'/../../init_new.php');
require_once XOOPS_TU_ROOT_PATH . '/class/xml/themesetparser.php';

class ThemeSetAuthorHandlerTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = 'ThemeSetAuthorHandler';
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
        $this->assertSame('author', $name);
    }
    
    public function test_handleBeginElement()
    {
        $instance = $this->object;

        $input = 'input';
        $parser = new XoopsThemeSetParser($input);
        $params = array();
        $x = $instance->handleBeginElement($parser, $params);
        $this->assertSame(array(), $parser->tempArr);
    }

    public function test_handleEndElement()
    {
        $instance = $this->object;
        
        $this->markTestIncomplete();
    }
}
