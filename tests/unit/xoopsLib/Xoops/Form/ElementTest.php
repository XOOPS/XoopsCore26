<?php
namespace Xoops\Form;

require_once(__DIR__.'/../../../init_new.php');

use Xoops\Form\Element;

class ElementTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Element
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = $this->getMockBuilder('Xoops\Form\Element')
            ->onlyMethods(['defaultRender'])
            ->getMock();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testRenderAttributeString()
    {
        $instance = $this->object;

        $arrAttr = array('title' => 'title_value', 'name' => 'name_value[]');
        $instance->setAll($arrAttr);

        $value = $instance->renderAttributeString();
        $expected = 'title="title_value" name="name_value[]" id="name_value" ';
        $this->assertSame($expected, $value);
    }

    public function testRenderAttributeString100()
    {
        $arrAttr = array('caption' => 'caption_value');
        $this->object->setAll($arrAttr);

        $value = $this->object->renderAttributeString();
        $expected = 'title="caption_value" id="0" ';
        $this->assertSame($expected, $value);

        $this->object->clear();
        $arrAttr = array('caption' => 'caption_value', ':pattern_description' => 'pattern description');
        $this->object->setAll($arrAttr);
        $value = $this->object->renderAttributeString();
        $expected = 'title="caption_value - pattern description" id="0" ';
        $this->assertSame($expected, $value);
    }

    public function testRenderAttributeString120()
    {
        $instance = $this->object;

        $arrAttr = array('title' => 'title_value', 'id'=>100);
        $instance->setAll($arrAttr);

        $value = $instance->renderAttributeString();
        $expected = 'title="title_value" id="100" ';
        $this->assertSame($expected, $value);
    }

    public function testGetValue()
    {
        $name = 'name';
        $this->object->setValue($name);
        $value = $this->object->getValue();
        $this->assertSame($name, $value);

        $names = array('name1', 'name2');
        $this->object->setValue($names);
        $value = $this->object->getValue();
        $this->assertSame($names, $value);
    }

    public function testGetName()
    {
        $name = 'name';
        $this->object->setName($name);
        $value = $this->object->getName();
        $this->assertSame($name, $value);
    }

    public function testGetAccessKey()
    {
        $key = 'name';
        $this->object->setAccessKey($key);
        $value = $this->object->getAccessKey();
        $this->assertSame($key, $value);
    }

    public function testGetAccessString()
    {
        $instance = $this->object;

        $key = 'name';
        $instance->setAccessKey($key);

        $str = 'this sentence contains name';
        $result = $instance->getAccessString($str);

        $expected = '<span style="text-decoration: underline;">n</span>ame';
        $this->assertTrue(false !== strpos($result, $expected));

        $str = 'this sentence contains no access string';
        $result = $instance->getAccessString($str);
        $this->assertSame($str, $result);
    }

    public function testGetClass()
    {
        $this->assertFalse($this->object->getClass());

        $name = 'name';
        $this->object->setClass($name);
        $value = $this->object->getClass();
        $this->assertSame($name, $value);
    }

    public function testGetPattern()
    {
        $name = 'name';
        $this->object->setPattern($name);
        $value = $this->object->getPattern();
        $this->assertSame($name, $value);
    }

    public function testGetPatternDescription()
    {
        $instance = $this->object;

        $name = 'name';
        $instance->setPattern($name);

        $result = $instance->getPatternDescription();
        $this->assertSame('', $result);

        $name = 'name';
        $pattern = 'pattern';
        $instance->setPattern($name, $pattern);

        $result = $instance->getPatternDescription();
        $this->assertSame($pattern, $result);
    }

    public function testRenderDatalist()
    {
        $instance = $this->object;

        $result = $instance->renderDatalist();
        $this->assertSame('', $result);

        $name = 'name';
        $instance->setName($name);

        $data = 'data';
        $instance->setDatalist($data);

        $result = $instance->renderDatalist();
        $expected = "\n" . '<datalist id="list_' . $name . '">' . "\n";
        $expected .= '<option value="' . htmlspecialchars($data, ENT_QUOTES) . '">' . "\n";
        $expected .= '</datalist>' . "\n";

        $this->assertSame($expected, $result);
    }

    public function testRenderDatalist100()
    {
        $instance = $this->object;

        $result = $instance->renderDatalist();
        $this->assertSame('', $result);

        $name = 'name';
        $instance->setName($name);

        $data = array('key1'=>'value1','key2'=>'value2',);
        $instance->setDatalist($data);

        $result = $instance->renderDatalist();
        $expected = "\n" . '<datalist id="list_' . $name . '">' . "\n";
        foreach ($data as $item) {
            $expected .= '<option value="' . htmlspecialchars($item, ENT_QUOTES) . '">' . "\n";
        }
        $expected .= '</datalist>' . "\n";

        $this->assertSame($expected, $result);
    }

    public function testIsDatalist()
    {
        $instance = $this->object;

        $result = $instance->isDatalist();
        $this->assertFalse($result);

        $data = 'data';
        $instance->setDatalist($data);

        $result = $instance->isDatalist();
        $this->assertTrue($result);
    }

    public function testGetCaption()
    {
        $name = 'name';
        $this->object->setCaption($name);
        $value = $this->object->getCaption();
        $this->assertSame($name, $value);
    }

    public function testGetTitle()
    {
        $name = 'name';
        $this->object->setTitle($name);
        $value = $this->object->getTitle();
        $this->assertSame($name, $value);

        $name = 'another name';
        $this->object->remove('title');
        $this->object->set('caption', $name);
        $value = $this->object->getTitle();
        $this->assertSame($name, $value);
        $desc = 'description';
        $this->object->set(':pattern_description', $desc);
        $value = $this->object->getTitle();
        $this->assertSame($name . ' - ' . $desc, $value);
    }

    public function testGetDescription()
    {
        $name = 'name';
        $this->object->setDescription($name);
        $value = $this->object->getDescription();
        $this->assertSame($name, $value);
    }

    public function testIsHidden()
    {
        $value = $this->object->isHidden();
        $this->assertFalse($value);
        $this->object->setHidden();
        $value = $this->object->isHidden();
        $this->assertTrue($value);
    }

    public function testIsRequired()
    {
        $value = $this->object->isRequired();
        $this->assertFalse($value);
        $this->object->setRequired();
        $value = $this->object->isRequired();
        $this->assertTrue($value);
    }

    public function testGetExtra()
    {
        $name = 'name';
        $this->object->setExtra('one');
        $value = $this->object->getExtra();
        $this->assertSame('one', $value);

        $this->object->setExtra('two');
        $value = $this->object->getExtra();
        $this->assertSame('one two', $value);

        $this->object->setExtra('three', true);
        $value = $this->object->getExtra(true);
        $this->assertSame('three', $value);
    }

    public function testRenderValidationJS()
    {
        $value = $this->object->renderValidationJS();
        $this->assertFalse($value);

        $this->object->setRequired();
        $this->object->setName('rendertest');
        $value = $this->object->renderValidationJS();
        $this->assertNotFalse($value);

        $this->object->remove('required');

        $this->object->addCustomValidationCode('');
        $value = $this->object->renderValidationJS();
        $this->assertEquals($value, '');

        $this->object->addCustomValidationCode('');
        $value = $this->object->renderValidationJS();
        $this->assertEquals($value, "\n");

        $this->object->addCustomValidationCode('', true);
        $value = $this->object->renderValidationJS();
        $this->assertEquals($value, '');
    }

    public function test_hasClassLike()
    {
        $name = 'class';
        $stem = 'stem';
        $this->assertFalse($this->object->hasClassLike($stem));
        $this->object->set($name, 'notstem');
        $this->assertFalse($this->object->hasClassLike($stem));
        $this->object->add($name, $stem);
        $this->assertNotFalse($this->object->hasClassLike($stem));
        $this->object->set($name, 'stuff');
        $this->object->add($name, 'stem3 fred');
        $this->assertNotFalse($this->object->hasClassLike($stem));
    }

    public function test_setWithDefaults()
    {
        $name = 'color';
        $enum = ['red', 'blue', 'green'];
        $default = 'black';
        $this->object->setWithDefaults($name, '', $default, $enum);
        $this->assertEquals($default, $this->object->get($name));
        $this->object->setWithDefaults($name, 'purple', $default, $enum);
        $this->assertEquals($default, $this->object->get($name));
        $this->object->setWithDefaults($name, 'blue', $default, $enum);
        $this->assertEquals('blue', $this->object->get($name));
    }

    public function test_setIfNotEmpty()
    {
        $name = 'name';
        $value = 'test';
        $this->assertFalse($this->object->has($name));
        $this->object->setIfNotEmpty($name, '');
        $this->assertFalse($this->object->has($name));

        $this->object->setIfNotEmpty($name, $value);
        $actual = $this->object->get($name);
        $this->assertSame($value, $actual);

        $this->object->setIfNotEmpty($name, 'this_will_not_be_set');
        $actual = $this->object->get($name);
        $this->assertSame($value, $actual);
    }

    public function test_setIfNotSet()
    {
        $name = 'name';
        $value = 'test';
        $this->assertFalse($this->object->has($name));
        $this->object->setIfNotSet($name, $value);
        $actual = $this->object->get($name);
        $this->assertSame($value, $actual);

        $this->object->setIfNotSet($name, 'this_will_not_be_set');
        $actual = $this->object->get($name);
        $this->assertSame($value, $actual);
    }

    public function test_arrayAccess()
    {
        $key = 'value';
        $value = 'testvalue';
        $instance = new Raw($value);
        //$this->assertFalse(isset($instance[$key]));
        $instance->setValue($value);
        $this->assertTrue(isset($instance[$key]));
        $this->assertEquals($value, $instance[$key]);

        $key = 'name';
        $value = 'testname';
        $this->assertFalse(isset($instance[$key]));
        $instance->setName($value);
        $this->assertTrue(isset($instance[$key]));
        $this->assertEquals($value, $instance[$key]);

        $key = 'accesskey';
        $value = 'testkey';
        $this->assertFalse(isset($instance[$key]));
        $instance->setAccessKey($value);
        $this->assertTrue(isset($instance[$key]));
        $this->assertEquals($value, $instance[$key]);

        $key = 'class';
        $value = 'testclass';
        $this->assertFalse(isset($instance[$key]));
        $instance->setClass($value);
        $this->assertTrue(isset($instance[$key]));
        $this->assertEquals([$value], $instance[$key]);

        $key = 'pattern';
        $value = 'testpattern';
        $this->assertFalse(isset($instance[$key]));
        $instance->setPattern($value, 'testdesc');
        $this->assertTrue(isset($instance[$key]));
        $this->assertEquals($value, $instance[$key]);
        $this->assertEquals('testdesc', $instance[':pattern_description']);

        $key = 'datalist';
        $value = 'testdatalist';
        $this->assertFalse(isset($instance[$key]));
        $instance->setDatalist($value);
        $this->assertTrue(isset($instance[$key]));
        $this->assertEquals([$value], $instance[$key]);

        $key = 'caption';
        $value = 'testcaption';
        $this->assertFalse(isset($instance[$key]));
        $instance->setCaption($value);
        $this->assertTrue(isset($instance[$key]));
        $this->assertEquals($value, $instance[$key]);

        $key = 'title';
        $value = 'testtitle';
        $this->assertFalse(isset($instance[$key]));
        $instance->setTitle($value);
        $this->assertTrue(isset($instance[$key]));
        $this->assertEquals($value, $instance[$key]);

        $key = 'description';
        $value = 'testdescription';
        $this->assertFalse(isset($instance[$key]));
        $instance->setDescription($value);
        $this->assertTrue(isset($instance[$key]));
        $this->assertEquals($value, $instance[$key]);

        $key = 'hidden';
        $this->assertFalse($instance->offsetExists($key));
        $instance->setHidden();
        $this->assertTrue($instance->offsetExists($key));
        $this->assertNull($instance[$key]);

        $key = 'required';
        $this->assertFalse($instance->offsetExists($key));
        $instance->setRequired(true);
        $this->assertTrue($instance->offsetExists($key));
        $this->assertNull($instance[$key]);
    }
}
