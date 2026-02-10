<?php
namespace Xoops\Core\Text\Sanitizer;

require_once __DIR__.'/../../../../../init_new.php';

class ConfigurationAbstractTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigurationAbstract
     */
    protected $object;

    /**
     * @var \ReflectionClass
     */
    protected $reflectedObject;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = $this->getMockBuilder('\Xoops\Core\Text\Sanitizer\ConfigurationAbstract')
            ->onlyMethods([])
            ->getMock();
        $this->reflectedObject = new \ReflectionClass('\Xoops\Core\Text\Sanitizer\ConfigurationAbstract');
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
        $this->assertTrue($this->reflectedObject->isAbstract());
        $this->assertTrue($this->reflectedObject->hasMethod('get'));
        $this->assertTrue($this->reflectedObject->hasMethod('set'));
        $this->assertTrue($this->reflectedObject->hasMethod('getAll'));
        $this->assertTrue($this->reflectedObject->hasMethod('getNames'));
        $this->assertTrue($this->reflectedObject->hasMethod('has'));
        $this->assertTrue($this->reflectedObject->hasMethod('remove'));
        $this->assertTrue($this->reflectedObject->hasMethod('clear'));
        $this->assertTrue($this->reflectedObject->hasMethod('setAll'));
        $this->assertTrue($this->reflectedObject->hasMethod('setMerge'));
        $this->assertTrue($this->reflectedObject->hasMethod('setArrayItem'));
        $this->assertTrue($this->reflectedObject->hasMethod('getAllLike'));
    }

    public function testGet()
    {
        $this->assertNull($this->object->get('--NoNameLikeThisAtAll--'));
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
