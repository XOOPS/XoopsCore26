<?php
require_once(__DIR__ . '/../../../init_new.php');

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

    public function testGetUrl()
    {
        $varname = 'RequestTest';

        $_REQUEST[$varname] = 'http://example.com/test.php';
        $this->assertEquals($_REQUEST[$varname], Request::getUrl($varname));

        $_REQUEST[$varname] = 'javascript:alert();';
        $this->assertEquals('', Request::getUrl($varname), Request::getUrl($varname));

        $_REQUEST[$varname] = 'modules/test/index.php';
        $this->assertEquals('modules/test/index.php', Request::getUrl($varname));
    }

    public function testGetPath()
    {
        $varname = 'RequestTest';

        $_REQUEST[$varname] = '/var/tmp';
        $this->assertEquals($_REQUEST[$varname], Request::getPath($varname), Request::getPath($varname));

        $_REQUEST[$varname] = ' modules/test/index.php?id=12 ';
        $this->assertEquals('modules/test/index.php?id=12', Request::getPath($varname), Request::getPath($varname));

        $_REQUEST[$varname] = '/var/tmp muck';
        $this->assertEquals('/var/tmp', Request::getPath($varname), Request::getPath($varname));
    }

    public function testGetEmail()
    {
        $varname = 'RequestTest';

        $_REQUEST[$varname] = 'fred@example.com';
        $this->assertEquals($_REQUEST[$varname], Request::getEmail($varname));

        $_REQUEST[$varname] = 'msdfniondfnlknlsdf';
        $this->assertEquals('', Request::getEmail($varname));

        $_REQUEST[$varname] = 'msdfniondfnlknlsdf';
        $default = 'nobody@localhost';
        $this->assertEquals($default, Request::getEmail($varname, $default));
    }

    public function testGetIPv4()
    {
        $varname = 'RequestTest';

        $_REQUEST[$varname] = '16.32.48.64';
        $this->assertEquals($_REQUEST[$varname], Request::getIP($varname));

        $_REQUEST[$varname] = '316.32.48.64';
        $this->assertEquals('', Request::getIP($varname));

        $_REQUEST[$varname] = '316.32.48.64';
        $default = '0.0.0.0';
        $this->assertEquals($default, Request::getIP($varname, $default));

    }

    public function testGetIPv6()
    {
        $varname = 'RequestTest';

        $_REQUEST[$varname] = 'FE80:0000:0000:0000:0202:B3FF:FE1E:8329';
        $this->assertEquals($_REQUEST[$varname], Request::getIP($varname));

        $_REQUEST[$varname] = 'FE80::0202:B3FF:FE1E:8329';
        $this->assertEquals($_REQUEST[$varname], Request::getIP($varname));

        $_REQUEST[$varname] = 'GE80::0202:B3FF:FE1E:8329';
        $this->assertEquals('', Request::getIP($varname));

        $_REQUEST[$varname] = '::ffff:16.32.48.64';
        $this->assertEquals($_REQUEST[$varname], Request::getIP($varname));
    }

    public function testGetDateTime()
    {
        $varname = 'datetimetest';

        \Xoops\Locale::setTimeZone(new \DateTimeZone('UTC'));
        \Xoops\Locale::setCurrent('en_US');
        $exampleDate = '12/14/2015';
        $exampleTime = '12:10 AM';
        $_REQUEST[$varname] = $exampleDate;
        $actual = Request::getDateTime($varname);

        $this->assertInstanceOf('\DateTime', $actual);
        $this->assertEquals($exampleDate, $actual->format('m/d/Y'));

        $_REQUEST[$varname] = ['date' => $exampleDate, 'time' => $exampleTime];
        $actual = Request::getDateTime($varname);

        $this->assertInstanceOf('\DateTime', $actual);
        $this->assertEquals($exampleDate, $actual->format('m/d/Y'));
        $this->assertEquals($exampleTime, $actual->format('h:i A'));
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
