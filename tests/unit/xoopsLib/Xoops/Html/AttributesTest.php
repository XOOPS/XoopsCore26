<?php
require_once(__DIR__.'/../../../init_new.php');

use Xoops\Html\Attributes;

class AttributesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Attributes
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new Attributes;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testContracts()
    {
        $this->assertInstanceOf('\Xoops\Html\Attributes', $this->object);
        $this->assertInstanceOf('\Xoops\Core\AttributeInterface', $this->object);
        $this->assertInstanceOf('\ArrayObject', $this->object);
    }

    public function test__construct()
    {
        $expected = ['a'=>'1', 'key'=>'value', 'required' => null ];
        $instance = new Attributes($expected);
        $actual = $instance->getAll();
        $this->assertSame($expected, $actual);
    }

    public function testSetAttribute()
    {
        $instance = $this->object;

        $key = 'key';
        $value = 'value';
        $instance->set($key,$value);
        $result = $instance->get($key);
        $this->assertSame($value, $result);
    }

    public function testUnsetAttribute()
    {
        $instance = $this->object;

        $key = 'key';
        $value = 'value';
        $instance->set($key,$value);
        $result = $instance->get($key);
        $this->assertSame($value, $result);

        $instance->remove($key);

        $result = $instance->get($key);
        $this->assertFalse($result);
    }

    public function testHasAttribute()
    {
        $instance = $this->object;

        $key = 'key';
        $value = 'value';
        $instance->set($key,$value);
        $result = $instance->get($key);
        $this->assertSame($value, $result);

        $result = $instance->has($key);
        $this->assertTrue($result);

        $result = $instance->has('key_not_found');
        $this->assertFalse($result);
    }

    public function testAdd()
    {
        $instance = $this->object;

        $strAttr = 'value1 value2 value3';
        $instance->add('key', $strAttr);

        $result = $instance->get('key');
        $this->assertTrue(is_array($result));
        $this->assertTrue(in_array('value1', $result));
        $this->assertTrue(in_array('value2', $result));
        $this->assertTrue(in_array('value3', $result));

        $instance->add('key', 'value4');

        $result = $instance->get('key');
        $this->assertTrue(is_array($result));
        $this->assertTrue(in_array('value1', $result));
        $this->assertTrue(in_array('value2', $result));
        $this->assertTrue(in_array('value3', $result));
        $this->assertTrue(in_array('value4', $result));

        $instance->set('key2', 'value4');

        $result = $instance->get('key2');
        $this->assertFalse(is_array($result));
        $this->assertEquals('value4', $result);

        $arrayAttr = ['value1', 'value2', 'value3'];
        $instance->add('key2', $arrayAttr);
        $result = $instance->get('key2');
        $this->assertTrue(is_array($result));
        $this->assertTrue(in_array('value1', $result));
        $this->assertTrue(in_array('value2', $result));
        $this->assertTrue(in_array('value3', $result));
        $this->assertTrue(in_array('value4', $result));
    }

    public function testRenderAttributeString()
    {
        $instance = $this->object;

        $arrAttr = array('key1' =>'value1', 'key2' => 'value2', 'key3' => 'value3');
        $instance->setAll($arrAttr);

        $result = $instance->renderAttributeString();
        $expected = 'key1="value1" key2="value2" key3="value3" ';
        $this->assertSame($expected, $result);
    }

    public function testRenderAttributeString100()
    {
        $instance = $this->object;

        $strAttr = 'value1 value2 value3';
        $instance->add('key', $strAttr);

        $result = $instance->renderAttributeString();
        $expected = 'key="value1 value2 value3" ';
        $this->assertSame($expected, $result);

        $instance->set(':control', 'shouldnotrender');
        $result = $instance->renderAttributeString();
        $expected = 'key="value1 value2 value3" ';
        $this->assertSame($expected, $result);

        $instance->set('required');
        $result = $instance->renderAttributeString();
        $expected = 'key="value1 value2 value3" required ';
        $this->assertSame($expected, $result);
    }

    public function testRenderAttributeString200()
    {
        $instance = $this->object;

        $instance->set('name', 'fred');
        $instance->set('multiple');

        $result = $instance->renderAttributeString();
        $expected = 'name="fred[]" multiple ';
        $this->assertSame($expected, $result);
    }

    public function testGet()
    {
        $this->assertFalse($this->object->get('--NoNameLikeThisAtAll--'));
        $this->assertTrue($this->object->get('--NoNameLikeThisAtAll--', true));
        $this->assertSame('OK', $this->object->get('testvalue', 'OK'));
    }

    public function testSet()
    {
        $this->object->set('testvalue', 'OK');
        $this->assertSame('OK', $this->object->get('testvalue', 'NotOK'));
    }

    public function testGetAll()
    {
        $this->object->set('test1', 'OK1');
        $this->object->set('test2', 'OK2');
        $all = $this->object->getAll();
        $this->assertArrayHasKey('test1', $all);
        $this->assertArrayHasKey('test2', $all);
        $this->assertEquals('OK1', $all['test1']);
        $this->assertEquals('OK2', $all['test2']);
    }

    public function testGetNames()
    {
        $this->object->set('test1', 'OK1');
        $this->object->set('test2', 'OK2');
        $all = $this->object->getNames();
        $this->assertEquals(array('test1', 'test2'), $all);
    }

    public function testHas()
    {
        $this->object->set('test1', 'OK1');
        $this->object->set('test2', 'OK2');
        $this->assertTrue($this->object->has('test1'));
        $this->assertTrue($this->object->has('test2'));
        $this->assertFalse($this->object->has('test3'));
    }

    public function testRemove()
    {
        $this->object->set('test1', 'OK1');
        $this->object->set('test2', 'OK2');
        $this->assertTrue($this->object->has('test1'));
        $this->assertTrue($this->object->has('test2'));
        $this->object->remove('test1');
        $this->assertFalse($this->object->has('test1'));
    }

    public function testClear()
    {
        $this->object->set('test1', 'OK1');
        $this->object->set('test2', 'OK2');
        $this->assertTrue($this->object->has('test1'));
        $this->assertTrue($this->object->has('test2'));
        $this->object->clear();
        $this->assertFalse($this->object->has('test1'));
        $this->assertFalse($this->object->has('test2'));
    }

    public function testSetAll()
    {
        $this->object->set('test1', 'OK1');
        $this->object->set('test2', 'OK2');
        $this->assertTrue($this->object->has('test1'));
        $this->assertTrue($this->object->has('test2'));

        $replacements = array(
            'test3' => 'OK3',
            'test4' => 'OK4',
        );
        $oldValues = $this->object->setAll($replacements);
        $this->assertArrayHasKey('test1', $oldValues);
        $this->assertArrayHasKey('test2', $oldValues);
        $this->assertArrayNotHasKey('test3', $oldValues);
        $this->assertArrayNotHasKey('test4', $oldValues);
        $this->assertTrue($this->object->has('test3'));
        $this->assertTrue($this->object->has('test4'));
        $this->assertFalse($this->object->has('test1'));
        $this->assertFalse($this->object->has('test2'));
        $this->assertSame('OK3', $this->object->get('test3'));
        $this->assertSame('OK4', $this->object->get('test4'));
    }

    public function testSetMerge()
    {
        $this->object->set('test1', 'OK1');
        $this->object->set('test2', 'OK2');

        $this->assertTrue($this->object->has('test1'));
        $this->assertTrue($this->object->has('test2'));

        $replacements = array(
            'test2' => 'OK2new',
            'test3' => 'OK3',
        );
        $this->object->setMerge($replacements);

        $this->assertTrue($this->object->has('test1'));
        $this->assertTrue($this->object->has('test2'));
        $this->assertTrue($this->object->has('test3'));

        $this->assertSame('OK1', $this->object->get('test1'));
        $this->assertSame('OK2new', $this->object->get('test2'));
        $this->assertSame('OK3', $this->object->get('test3'));
    }

    public function testSetArrayItem()
    {
        $this->object->setArrayItem('test', 'a', 'OK1');
        $this->object->setArrayItem('test', 'b', 'OK2');

        $expected = array(
            'a' => 'OK1',
            'b' => 'OK2',
        );
        $this->assertEquals($expected, $this->object->get('test'));

        $this->object->set('test', 'NOTOK1');
        $this->object->setArrayItem('test', null, 'OK1');
        $this->object->setArrayItem('test', null, 'OK2');

        $expected = array(
            0 => 'OK1',
            1 => 'OK2',
        );
        $actual = $this->object->get('test');
        $this->assertEquals($expected, $actual);
    }

    public function testGetAllLike()
    {
        $this->object->set('oddball', 'odd');
        $this->object->set('test1', 'OK1');
        $this->object->set('text1', 'NOTOK1');
        $this->object->set('text2', 'NOTOK2');
        $this->object->set('test2', 'OK2');

        $subset = $this->object->getAllLike('test');
        $this->assertCount(2, $subset);
        $this->assertArrayHasKey('test1', $subset);
        $this->assertArrayHasKey('test2', $subset);
        $this->assertEquals('OK1', $subset['test1']);
        $this->assertEquals('OK2', $subset['test2']);

        $subset = $this->object->getAllLike('oddball');
        $this->assertCount(1, $subset);
        $this->assertArrayHasKey('oddball', $subset);
        $this->assertEquals('odd', $subset['oddball']);

        $subset = $this->object->getAllLike('garbage');
        $this->assertCount(0, $subset);

        $subset = $this->object->getAllLike();
        $this->assertArrayHasKey('oddball', $subset);
        $this->assertArrayHasKey('test1', $subset);
        $this->assertArrayHasKey('test2', $subset);
        $this->assertArrayHasKey('text1', $subset);
        $this->assertArrayHasKey('text2', $subset);
        $this->assertCount(5, $subset);
    }

    public function testArrayAccess()
    {
        $this->object['test1'] = 'OK1';
        $this->object->set('test2', 'OK2');

        $this->assertSame('OK1', $this->object->get('test1'));
        $this->assertSame('OK2', $this->object['test2']);
        $this->assertEquals(2, count($this->object));
        $i = 0;
        foreach ($this->object as $v) {
            ++$i;
        }
        $this->assertEquals($i, count($this->object));
        $this->assertSame('OK2', $v);
    }
}
