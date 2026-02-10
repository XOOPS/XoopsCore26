<?php
require_once(__DIR__.'/../../init_new.php');
require_once XOOPS_TU_ROOT_PATH . '/class/xml/themesetparser.php';

class ThemeSetImageHandlerTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = 'ThemeSetImageHandler';
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
        $this->assertSame('image', $name);
    }
    
    public function test_handleBeginElement()
    {
        $instance = $this->object;

        $input = 'input';
        $parser = new XoopsThemeSetParser($input);
        $attributes = array('name' => 'name');
        $instance->handleBeginElement($parser, $attributes);
        $this->assertSame('name', $parser->getTempArr('name'));
    }

    public function test_handleEndElement()
    {
        $instance = $this->object;

        $input = 'input';
        $parser = new XoopsThemeSetParser($input);
        $attributes = array('name' => 'name');
        $instance->handleBeginElement($parser, $attributes);

        $instance->handleEndElement($parser);
        $x = $parser->getImagesData();
        $this->assertTrue(is_array($x));
        $this->assertSame('name', $x[0]['name']);
    }
}
