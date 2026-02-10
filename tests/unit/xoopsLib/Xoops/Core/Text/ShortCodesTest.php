<?php

/**
 * Many tests adapted from Badcow/Shortcodes
 * @link https://github.com/Badcow/Shortcodes/blob/master/tests/ShotcodesTest.php
 */
namespace Xoops\Core\Text;

require_once __DIR__.'/../../../../init_new.php';

class ShortCodesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ShortCodes
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new ShortCodes;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testAddShortcode_exception()
    {
        $this->expectException('\ErrorException');
        $this->object->addShortcode('error', 'thisIsNotCallable');
    }

    public function testRemoveShortcode()
    {
        $this->object->addShortcode('test', array($this, 'dummyFunction_test'));

        $this->assertTrue($this->object->hasShortcode('test'));
        $this->object->removeShortcode('test');
        $this->assertFalse($this->object->hasShortcode('test'));
    }

    public function testGetShortcodes()
    {
        $this->object->addShortcode('test', array($this, 'dummyFunction_test'));

        $this->assertArrayHasKey('test', $this->object->getShortcodes());
    }

    public function testHasShortcode()
    {
        $this->object->addShortcode('test', array($this, 'dummyFunction_test'));

        $this->assertTrue($this->object->hasShortcode('test'));
        $this->assertFalse($this->object->hasShortcode('foobar'));
    }

    public function testContentHasShortcode()
    {
        $this->object->addShortcode('test', array($this, 'dummyFunction_test'));
        $this->object->addShortcode('yasct', array($this, 'dummyFunction_test'));
        $content1 = 'Hello my name is [test name="Sam"]!';
        $content2 = 'Hello my name is Sam!';

        $this->assertTrue($this->object->contentHasShortcode($content1, 'test'));
        $this->assertFalse($this->object->contentHasShortcode($content1, 'yasct'));
        $this->assertFalse($this->object->contentHasShortcode($content2, 'test'));

        $this->assertFalse($this->object->contentHasShortcode($content1, 'foobar'));
        $this->assertFalse($this->object->contentHasShortcode($content2, 'foobar'));
    }

    public function testStripAllShortcodes()
    {
        $content = 'Hello [enclosed]my name is sam[/enclosed] [[test]]';
        $expected = 'Hello  [test]';

        $this->assertEquals($content, $this->object->stripAllShortcodes($content));

        $this->object->addShortcode('enclosed', array($this, 'dummyFunction_enclosed'));
        $this->object->addShortcode('test', array($this, 'dummyFunction_test'));

        $this->assertEquals($expected, $this->object->stripAllShortcodes($content));

        $content = 'Hello [[enclosed id=6 /]] [[test]nothing[/test]]';
        $expected = 'Hello [enclosed id=6 /] [test]nothing[/test]';
        $actual = $this->object->stripAllShortcodes($content);
        $this->assertEquals($expected, $actual);
    }

    public function testShortcodeAttributes()
    {
        $defaults = ['a' => 'alpha', 'b' => 'bravo', 'c' => 'charley'];
        $input = ['a' => 'alpha', 'b' => 'beta', 'e' => 'echo'];

        $actual = $this->object->shortcodeAttributes($defaults, $input);
        $expected = ['a' => 'alpha', 'b' => 'beta', 'c' => 'charley'];
        $this->assertSame($expected, $actual);
    }

    /**
     * @var string
     */
    private $qbf = 'The quick brown fox jumps over the lazy dog';

    public function testKeyValuePairAttributes()
    {
        $this->object->addShortcode('test', array($this, 'dummyFunction_test'));

        $content = 'Hello my name is [test name="Sam"]!';
        $expectation = 'Hello my name is name: Sam!';

        $this->assertEquals($expectation, $this->object->process($content));
    }

    public function testMultipleShortcodes()
    {
        $this->object->addShortcode('test', array($this, 'dummyFunction_test'));
        $this->object->addShortcode('qbf', array($this, 'dummyFunction_qbf'));

        $content = 'Hello my name is [test name="Sam"]! Did you know that [qbf]';
        $expectation = 'Hello my name is name: Sam! Did you know that ' . $this->qbf;

        $this->assertEquals($expectation, $this->object->process($content));
    }

    public function testEnclosedShortcodes()
    {
        $this->object->addShortcode('enclosed', array($this, 'dummyFunction_enclosed'));

        $content = 'Hello [enclosed]my name is sam[/enclosed]';
        $expectation = 'Hello my name is sam';

        $this->assertEquals($expectation, $this->object->process($content));
    }

    public function testNoShortcodesDefined()
    {
        $content = 'Hello [enclosed]my name is sam[/enclosed]';

        $this->assertEquals($content, $this->object->process($content));
    }

    public function testSelfClosedTags()
    {
        $this->object->addShortcode('enclosed', array($this, 'dummyFunction_enclosed'));

        $content = 'Hello [enclosed /]';
        $expectation = 'Hello ';

        $this->assertEquals($expectation, $this->object->process($content));
    }

    public function testKeyValuePairAttributes2()
    {
        $this->object->addShortcode('test', array($this, 'dummyFunction_test'));

        $content = 'Hello my name is [test name=\'Sam\']! [test job=programmer /]';
        $expectation = 'Hello my name is name: Sam! job: programmer';

        $this->assertEquals($expectation, $this->object->process($content));
    }

    public function testKeyValuePairAttributes3()
    {
        $this->object->addShortcode('test', array($this, 'dummyFunction_test'));

        $content = 'Hello my name is [test "Sam"]! [test programmer]';
        $expectation = 'Hello my name is 0: Sam! 0: programmer';

        $this->assertEquals($expectation, $this->object->process($content));
    }

    public function testKeyValuePairAttributes4()
    {
        $this->object->addShortcode('test', array($this, 'dummyFunction_test'));

        $content = 'Hello my name is [test \'Sam\']!';
        $expectation = 'Hello my name is 0: \'Sam\'!';

        $this->assertEquals($expectation, $this->object->process($content));
    }

    public function testEscaping()
    {
        $shortcodes = new Shortcodes;
        $shortcodes->addShortcode('test', array($this, 'dummyFunction_test'));
        $content = 'Hello my name is [[test name="Sam"]]!';
        //$expectation = 'Hello my name is [test name="Sam"]!';
        $expectation = 'Hello my name is &#91;test name="Sam"&#93!';

        $this->assertEquals($expectation, $shortcodes->process($content));
    }

    public function dummyFunction_test(array $attributes)
    {
        $returnStr = '';
        foreach ($attributes as $key => $attr) {
            $returnStr .= "$key: $attr";
        }

        return $returnStr;
    }

    public function dummyFunction_qbf(array $attributes)
    {
        return $this->qbf;
    }

    public function dummyFunction_enclosed(array $attributes, $content, $tagName)
    {
        return $content;
    }
}
