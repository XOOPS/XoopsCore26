<?php
require_once __DIR__.'/../../../init_new.php';

use Xoops\Core\HttpRequest;

class HttpRequestTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var HttpRequest
     */
    protected $object;
    protected $myClass = '\Xoops\Core\HttpRequest';
    protected $save_SERVER = null;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = HttpRequest::getInstance();
        $this->save_SERVER = $_SERVER;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        $_SERVER = $this->save_SERVER;
    }

    public function testGetInstance()
    {
        $instance = HttpRequest::getInstance();
        $this->assertInstanceOf($this->myClass, $instance);

        $this->assertSame($instance, $this->object);
    }

    public function testGetHeader()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testGetScheme()
    {
        $instance = $this->object;

        unset($_SERVER['HTTPS']);
        $result = $instance->getScheme();
        $this->assertSame('http', $result);

        $_SERVER['HTTPS'] = 'on';
        $result = $instance->getScheme();
        $this->assertSame('https', $result);
    }

    public function testGetHost()
    {
        $instance = $this->object;

        unset($_SERVER['HTTP_HOST']);
        $result = $instance->getHost();
        $this->assertSame('localhost', $result);

        $_SERVER['HTTP_HOST'] = 'dummy_http_host';
        $result = $instance->getHost();
        $this->assertSame($_SERVER['HTTP_HOST'], $result);
    }

    public function testGetUri()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testGetReferer()
    {
        $instance = $this->object;

        unset($_SERVER['HTTP_REFERER']);
        $result = $instance->getReferer();
        $this->assertSame('', $result);

        $_SERVER['HTTP_REFERER'] = 'dummy_http_referer';
        $result = $instance->getReferer();
        $this->assertSame($_SERVER['HTTP_REFERER'], $result);
    }

    public function testGetScriptName()
    {
        $instance = $this->object;

        unset($_SERVER['SCRIPT_NAME'], $_SERVER['ORIG_SCRIPT_NAME']);
        $result = $instance->getScriptName();
        $this->assertSame('', $result);

        $_SERVER['ORIG_SCRIPT_NAME'] = 'dummy_orig_script_name';
        $result = $instance->getScriptName();
        $this->assertSame($_SERVER['ORIG_SCRIPT_NAME'], $result);

        $_SERVER['SCRIPT_NAME'] = 'dummy_script_name';
        $result = $instance->getScriptName();
        $this->assertSame($_SERVER['SCRIPT_NAME'], $result);
    }

    public function testGetDomain()
    {
        if (!class_exists('Geekwright\RegDom\RegisteredDomain')) {
            $this->markTestSkipped('geekwright/regdom package not installed');
        }
        $instance = $this->object;

        $_SERVER['HTTP_HOST'] = 'subdomain.example.com';
        $result = $instance->getDomain();
        $this->assertSame('example.com', $result);
    }

    public function testGetSubdomains()
    {
        if (!class_exists('Geekwright\RegDom\RegisteredDomain')) {
            $this->markTestSkipped('geekwright/regdom package not installed');
        }
        $instance = $this->object;

        $_SERVER['HTTP_HOST'] = 'subdomain.example.com';
        $result = $instance->getSubdomains();
        $this->assertSame('subdomain', $result);
    }

    public function testGetClientIp()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testGetUrl()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testGetEnv()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function test_getEnv_https()
    {
        $instance = $this->object;

        $_SERVER['HTTPS'] = 'off';
        $x = $instance->getEnv('HTTPS');
        $this->assertFalse($x);

        $_SERVER['HTTPS'] = 'on';
        $x = $instance->getEnv('HTTPS');
        $this->assertTrue($x);

        $_SERVER['SCRIPT_URI'] = 'https://localhost';
        unset($_SERVER['HTTPS']);
        $x = $instance->getEnv('HTTPS');
        $this->assertTrue($x);

        $_SERVER['SCRIPT_URI'] = 'http://localhost';
        unset($_SERVER['HTTPS']);
        $x = $instance->getEnv('HTTPS');
        $this->assertFalse($x);

    }

    public function test_getEnv_script_filename()
    {
        $instance = $this->object;

        $_SERVER = array();
        $_SERVER['PATH_TRANSLATED'] = '//a///test//test.php';
        $this->assertSame('/a/test/test.php', $instance->getEnv('SCRIPT_FILENAME'));

        $_SERVER['PATH_TRANSLATED'] = '\\a\\test\\test.php';
        $this->assertSame('\a\test\test.php', $instance->getEnv('SCRIPT_FILENAME'));

    }

    public function test_getEnv_document_root()
    {
        $_SERVER = array();
        $_SERVER['SCRIPT_NAME'] = 'test/filename';
        $_SERVER['SCRIPT_FILENAME'] = '/a/test/filename.php';
        $this->assertSame('/a/', $this->object->getEnv('DOCUMENT_ROOT'));

    }

    public function test_getEnv_php_self()
    {
        $_SERVER = array();
        $_SERVER['DOCUMENT_ROOT'] = '/a/dir';
        $_SERVER['SCRIPT_FILENAME'] = '/a/dir/test/filename.php';
        $this->assertSame('/test/filename.php', $this->object->getEnv('PHP_SELF'));

    }

    public function test_getEnv_cgi_mode()
    {
        $b = (PHP_SAPI === 'cgi');
        $this->assertSame($b, $this->object->getEnv('CGI_MODE'));
    }

    public function test_getEnv_http_base()
    {
        if (!class_exists('Geekwright\RegDom\RegisteredDomain')) {
            $this->markTestSkipped('geekwright/regdom package not installed');
        }
        $instance = $this->object;

        $_SERVER['HTTP_HOST'] = 'localhost';
        unset($_SERVER['HTTP_BASE']);
        $this->assertSame('.localhost', $instance->getEnv('HTTP_BASE'));

        $_SERVER['HTTP_HOST'] = 'com.ar'; // invalid - only a public prefix
        unset($_SERVER['HTTP_BASE']);
        $this->assertSame('invalid', $instance->getEnv('HTTP_BASE', 'invalid'));
        $this->assertNull($instance->getEnv('HTTP_BASE'));

        $_SERVER['HTTP_HOST'] = 'example.ar';
        unset($_SERVER['HTTP_BASE']);
        $this->assertSame('.example.ar', $instance->getEnv('HTTP_BASE'));

        $_SERVER['HTTP_HOST'] = 'example.com';
        unset($_SERVER['HTTP_BASE']);
        $this->assertSame('.example.com', $instance->getEnv('HTTP_BASE'));

        $_SERVER['HTTP_HOST'] = 'www.example.com';
        unset($_SERVER['HTTP_BASE']);
        $this->assertSame('.example.com', $instance->getEnv('HTTP_BASE'));

        $_SERVER['HTTP_HOST'] = 'subdomain.example.com';
        unset($_SERVER['HTTP_BASE']);
        $this->assertSame('.example.com', $instance->getEnv('HTTP_BASE'));

        $_SERVER['HTTP_HOST'] = 'example.com.ar';
        unset($_SERVER['HTTP_BASE']);
        $this->assertSame('.example.com.ar', $instance->getEnv('HTTP_BASE'));

        $_SERVER['HTTP_HOST'] = 'www.example.com.ar';
        unset($_SERVER['HTTP_BASE']);
        $this->assertSame('.example.com.ar', $instance->getEnv('HTTP_BASE'));

        $_SERVER['HTTP_HOST'] = 'subdomain.example.com.ar';
        unset($_SERVER['HTTP_BASE']);
        $this->assertSame('.example.com.ar', $instance->getEnv('HTTP_BASE'));

        $_SERVER['HTTP_HOST'] = 'double.subdomain.example.com';
        unset($_SERVER['HTTP_BASE']);
        $this->assertSame('.example.com', $instance->getEnv('HTTP_BASE'));

        $_SERVER['HTTP_HOST'] = 'double.subdomain.example.com.ar';
        unset($_SERVER['HTTP_BASE']);
        $this->assertSame('.example.com.ar', $instance->getEnv('HTTP_BASE'));

        $_SERVER['HTTP_HOST'] = '中国化工集团公司.公司';
        unset($_SERVER['HTTP_BASE']);
        $this->assertSame('.中国化工集团公司.公司', $instance->getEnv('HTTP_BASE'));

    }

    public function testGetFiles()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testIs()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testAddDetector()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testClientAcceptsType()
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html,application/xml;q=0.9,image/webp';
        $this->assertTrue($this->object->clientAcceptsType('text/html'));
        $this->assertTrue($this->object->clientAcceptsType('application/xml'));
        $this->assertTrue($this->object->clientAcceptsType('image/webp'));
        $this->assertFalse($this->object->clientAcceptsType('image/jpeg'));
        $_SERVER['HTTP_ACCEPT'] = 'text/html,application/xml;q=0.9,image/*';
        $this->assertTrue($this->object->clientAcceptsType('image/webp'));
        $this->assertTrue($this->object->clientAcceptsType('image/jpeg'));
        $this->assertFalse($this->object->clientAcceptsType('application/shockwave'));
        $_SERVER['HTTP_ACCEPT'] = 'text/html,application/xml;q=0.9,*/*;q=0.1';
        $this->assertTrue($this->object->clientAcceptsType('application/shockwave'));
    }

    public function testGetAcceptMediaTypes()
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html,application/xml;q=0.9,*/*;q=0.1';
        $expected = array(
            'text/html' => 1,
            'application/xml' => 0.9,
            '*/*' => 0.1,
        );
        $actual = $this->object->getAcceptMediaTypes();
        $this->assertEquals($expected, $actual);
    }

    public function testGetAcceptedLanguages()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-ca,en;q=0.8,en-us;q=0.6,de-de;q=0.4,de;q=0.2';
        $expected = array(
            'en-ca' => 1,
            'en' => 0.8,
            'en-us' => 0.6,
            'de-de' => 0.4,
            'de' => 0.2,
        );
        $actual = $this->object->getAcceptedLanguages();
        $this->assertEquals($expected, $actual);
    }
}
