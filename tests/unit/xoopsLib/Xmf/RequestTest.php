<?php
namespace Xmf\Test;

use Xmf\Request;

class RequestTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Request
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new Request;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testGetMethod()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $method = Request::getMethod();
        $this->assertTrue(in_array($method, array('GET', 'HEAD', 'POST', 'PUT')));
    }

    public function testGetVar()
    {
        $varname = 'RequestTest';
        $value = 'testing';
        $_REQUEST[$varname] = $value;

        $this->assertEquals($value, Request::getVar($varname));
        $this->assertNull(Request::getVar($varname.'no-such-key'));
    }

    public function testGetInt()
    {
        $varname = 'RequestTest';

        $_REQUEST[$varname] = '9';
        $this->assertEquals(9, Request::getInt($varname));

        $_REQUEST[$varname] = '123fred5';
        $this->assertEquals(123, Request::getInt($varname));

        $_REQUEST[$varname] = '-123.45';
        $this->assertEquals(-123, Request::getInt($varname));

        $_REQUEST[$varname] = 'notanumber';
        $this->assertEquals(0, Request::getInt($varname));

        $this->assertEquals(0, Request::getInt($varname.'no-such-key'));


    }

    public function testGetFloat()
    {
        $varname = 'RequestTest';

        $_REQUEST[$varname] = '1.23';
        $this->assertEquals(1.23, Request::getFloat($varname));

        $_REQUEST[$varname] = '-1.23';
        $this->assertEquals(-1.23, Request::getFloat($varname));

        $_REQUEST[$varname] = '5.68 blah blah';
        $this->assertEquals(5.68, Request::getFloat($varname));

        $_REQUEST[$varname] = '1';
        $this->assertTrue(1.0 === Request::getFloat($varname));
    }

    public function testGetBool()
    {
        $varname = 'RequestTest';

        $_REQUEST[$varname] = '9';
        $this->assertTrue(Request::getBool($varname));

        $_REQUEST[$varname] = 'a string';
        $this->assertTrue(Request::getBool($varname));

        $_REQUEST[$varname] = true;
        $this->assertTrue(Request::getBool($varname));

        $_REQUEST[$varname] = '';
        $this->assertFalse(Request::getBool($varname));

        $_REQUEST[$varname] = false;
        $this->assertFalse(Request::getBool($varname));

        $this->assertFalse(Request::getBool($varname.'no-such-key'));
    }

    public function testGetWord()
    {
        $varname = 'RequestTest';

        $_REQUEST[$varname] = 'Lorem';
        $this->assertEquals('Lorem', Request::getWord($varname));

        $_REQUEST[$varname] = 'Lorem ipsum 88 59';
        $this->assertEquals('Loremipsum', Request::getWord($varname));

        $_REQUEST[$varname] = '.99 Lorem_ipsum @%&';
        $this->assertEquals('Lorem_ipsum', Request::getWord($varname));

        //echo Request::getWord($varname);
    }

    public function testGetCmd()
    {
        $varname = 'RequestTest';

        $_REQUEST[$varname] = 'Lorem';
        $this->assertEquals('lorem', Request::getCmd($varname));

        $_REQUEST[$varname] = 'Lorem ipsum 88 59';
        $this->assertEquals('loremipsum8859', Request::getCmd($varname));

        $_REQUEST[$varname] = '.99 Lorem_ipsum @%&';
        $this->assertEquals('.99lorem_ipsum', Request::getCmd($varname), Request::getCmd($varname));
    }

    public function testGetString()
    {
        $varname = 'RequestTest';

        $_REQUEST[$varname] = 'Lorem ipsum </i><script>alert();</script>';
        $this->assertEquals('Lorem ipsum alert();', Request::getString($varname));
    }

    public function testGetString2()
    {
        $varname = 'RequestTest';

        $safeTest = '<p>This is a <em>simple</em> test.</p>';
        $_POST[$varname] = $safeTest;

        $this->assertEquals('This is a simple test.', Request::getString($varname, '', 'POST'));
    }

    public function testGetStringAllowHtml()
    {
        $varname = 'RequestTest';

        $safeTest = '<p>This is a <em>simple</em> test.</p>';
        $_POST[$varname] = $safeTest;

        $this->assertEquals($safeTest, Request::getString($varname, '', 'POST', Request::MASK_ALLOW_HTML));
    }

    public function testGetStringAllowHtmlXss()
    {
        $varname = 'RequestTest';

        $xssTest = '<p>This is a <em>xss</em> <script>alert();</script> test.</p>';
        $_POST[$varname] = $xssTest;
        $xssTestExpect = '<p>This is a <em>xss</em> alert(); test.</p>';
        $this->assertEquals($xssTestExpect, Request::getString($varname, '', 'POST', Request::MASK_ALLOW_HTML));
    }

    public function testGetArray()
    {
        $varname = 'RequestTest';

        $testArray = array('one', 'two', 'three');
        $_REQUEST[$varname] = $testArray;

        $get = Request::getArray($varname, null, 'request');
        $this->assertTrue(is_array($get));
        $this->assertEquals($get, $testArray);

        $testArray2 = array('one', 'two', '<script>three</script>');
        $_REQUEST[$varname] = $testArray2;

        $get = Request::getArray($varname, null, 'request');
        $this->assertTrue(is_array($get));
        $this->assertEquals($get, $testArray);
    }

    public function testGetText()
    {
        $varname = 'RequestTest';

        $_REQUEST[$varname] = 'Lorem ipsum </i><script>alert();</script>';
        $this->assertEquals($_REQUEST[$varname], Request::getText($varname));
    }

    public function testHasVar()
    {
        $varname = 'RequestTest[HasVar]';
        $this->assertFalse(Request::hasVar($varname, 'GET'));
        Request::setVar($varname, 'OK', 'get');
        $this->assertTrue(Request::hasVar($varname, 'GET'));
    }

    public function testSetVar()
    {
        $varname = 'RequestTest';
        Request::setVar($varname, 'Porshca', 'get');
        $this->assertEquals($_REQUEST[$varname], 'Porshca');
    }

    public function testGet()
    {
        $varname = 'RequestTest';

        $_REQUEST[$varname] = 'Lorem';

        $get = Request::get('request');
        $this->assertTrue(is_array($get));
        $this->assertEquals('Lorem', $get[$varname]);

        unset($get);
        $_REQUEST[$varname] = '<i>Lorem ipsum </i><script>alert();</script>';
        $get = Request::get('request');
        $this->assertEquals('Lorem ipsum alert();', $get[$varname]);
    }

    public function testSet()
    {
        $varname = 'RequestTest';
        Request::set(array($varname => 'Pourquoi'), 'get');
        $this->assertEquals($_REQUEST[$varname], 'Pourquoi');
    }
}
