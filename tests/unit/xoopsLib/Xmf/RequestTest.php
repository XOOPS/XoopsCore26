<?php
namespace Xmf\Test;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Xmf\Request;

class RequestTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Request
     */
    protected $object;

    private ?\SessionHandlerInterface $sessionHandler = null;
    private ?\SessionHandlerInterface $defaultSessionHandler = null;

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

    // ported from XMF 1.3.2 tests/unit
    public function testSetVarEnv()
    {
        $varname = 'XMF_TEST_ENV_VAR';
        $value = 'env_test_value';
        Request::setVar($varname, $value, 'env');
        $this->assertArrayHasKey($varname, $_ENV);
        $this->assertEquals($value, $_ENV[$varname]);
        unset($_ENV[$varname]);
    }

    public function testSetVarServer()
    {
        $varname = 'XMF_TEST_SERVER_VAR';
        $value = 'server_test_value';
        Request::setVar($varname, $value, 'server');
        $this->assertArrayHasKey($varname, $_SERVER);
        $this->assertEquals($value, $_SERVER[$varname]);
        unset($_SERVER[$varname]);
    }

    #[RunInSeparateProcess]
    public function testGetHeaderReturnsStringHeader()
    {
        $_SERVER['HTTP_X_TEST_HEADER'] = 'header-value';

        $this->assertSame('header-value', Request::getHeader('X-Test-Header', 'default'));
    }

    #[RunInSeparateProcess]
    public function testGetHeaderReturnsDefaultForNonStringHeader()
    {
        $_SERVER['HTTP_X_TEST_HEADER'] = array('not-a-string');

        $this->assertSame('default', Request::getHeader('X-Test-Header', 'default'));
    }

    #[RunInSeparateProcess]
    public function testGetVarSessionWithActiveSession()
    {
        $this->startTestSession();
        $varname = 'RequestTestSession';
        $_SESSION[$varname] = 'session_value';

        try {
            $this->assertEquals('session_value', Request::getVar($varname, null, 'session'));
        } finally {
            unset($_SESSION[$varname]);
            $this->closeTestSession();
        }
    }

    #[RunInSeparateProcess]
    public function testGetVarSessionReturnsDefaultWhenKeyMissing()
    {
        $this->startTestSession();

        try {
            $this->assertNull(Request::getVar('no_such_session_key', null, 'session'));
            $this->assertEquals('fallback', Request::getVar('no_such_session_key', 'fallback', 'session'));
        } finally {
            $this->closeTestSession();
        }
    }

    #[RunInSeparateProcess]
    public function testGetVarSessionReturnsDefaultWhenNoSession()
    {
        $this->startTestSession();
        $this->closeTestSession();

        $this->assertNull(Request::getVar('any_key', null, 'session'));
        $this->assertEquals('default_val', Request::getVar('any_key', 'default_val', 'session'));
    }

    #[RunInSeparateProcess]
    public function testGetIntFromSession()
    {
        $this->startTestSession();
        $varname = 'RequestTestSessionInt';
        $_SESSION[$varname] = '42';

        try {
            $this->assertEquals(42, Request::getInt($varname, 0, 'session'));
        } finally {
            unset($_SESSION[$varname]);
            $this->closeTestSession();
        }
    }

    #[RunInSeparateProcess]
    public function testGetSessionHash()
    {
        $this->startTestSession();
        $varname = 'RequestTestSessionGet';
        $_SESSION[$varname] = 'get_session_value';

        try {
            $get = Request::get('session');
            $this->assertTrue(is_array($get));
            $this->assertEquals('get_session_value', $get[$varname]);
        } finally {
            unset($_SESSION[$varname]);
            $this->closeTestSession();
        }
    }

    #[RunInSeparateProcess]
    public function testGetSessionHashReturnsEmptyWhenNoSession()
    {
        $this->startTestSession();
        $this->closeTestSession();

        $get = Request::get('session');
        $this->assertTrue(is_array($get));
        $this->assertEmpty($get);
    }

    #[RunInSeparateProcess]
    public function testSetVarSession()
    {
        $this->startTestSession();
        $varname = 'XMF_TEST_SESSION_VAR';
        $value = 'session_set_value';

        try {
            Request::setVar($varname, $value, 'session');
            $this->assertArrayHasKey($varname, $_SESSION);
            $this->assertEquals($value, $_SESSION[$varname]);
        } finally {
            unset($_SESSION[$varname]);
            $this->closeTestSession();
        }
    }

    #[RunInSeparateProcess]
    public function testSetVarSessionIgnoredWhenNoSession()
    {
        $this->startTestSession();
        $this->closeTestSession();

        $varname = 'XMF_TEST_SESSION_NO_WRITE';
        Request::setVar($varname, 'should_not_persist', 'session');

        // check straight away: re-opening the session would reload $_SESSION and hide a write
        $this->assertArrayNotHasKey($varname, $_SESSION ?? []);
    }

    #[RunInSeparateProcess]
    public function testHasVarSession()
    {
        $this->startTestSession();
        $varname = 'RequestTestHasVarSession';

        try {
            $this->assertFalse(Request::hasVar($varname, 'session'));
            $_SESSION[$varname] = 'exists';
            $this->assertTrue(Request::hasVar($varname, 'session'));
        } finally {
            unset($_SESSION[$varname]);
            $this->closeTestSession();
        }

        $this->assertFalse(Request::hasVar($varname, 'session'));
    }

    /**
     * Attempt to start a session for testing.
     *
     * Uses an in-memory save handler so the tests do not depend on the CLI
     * session file handler working on the local machine.
     */
    private function startTestSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
        } else {
            $this->defaultSessionHandler ??= new \SessionHandler();
            $this->sessionHandler = new class implements \SessionHandlerInterface {
                private array $sessions = [];

                public function open(string $path, string $name): bool
                {
                    return true;
                }

                public function close(): bool
                {
                    return true;
                }

                public function read(string $id): string
                {
                    return $this->sessions[$id] ?? '';
                }

                public function write(string $id, string $data): bool
                {
                    $this->sessions[$id] = $data;
                    return true;
                }

                public function destroy(string $id): bool
                {
                    unset($this->sessions[$id]);
                    return true;
                }

                public function gc(int $max_lifetime): int|false
                {
                    return 0;
                }
            };

            session_set_save_handler($this->sessionHandler, true);
            $started = @session_start();
            if ($started === false || session_status() !== PHP_SESSION_ACTIVE) {
                $this->restoreDefaultSessionHandler();
                $this->sessionHandler = null;
                $this->markTestSkipped('Cannot start a session in this environment.');
            }

            $_SESSION = [];
        }
    }

    /**
     * Close any active session and verify it is no longer active.
     */
    private function closeTestSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            $_SESSION = [];
            session_write_close();
        }

        $this->restoreDefaultSessionHandler();
        $this->sessionHandler = null;

        $this->assertNotSame(
            PHP_SESSION_ACTIVE,
            session_status(),
            'Session should not be active after close.'
        );
    }

    private function restoreDefaultSessionHandler(): void
    {
        if ($this->defaultSessionHandler instanceof \SessionHandlerInterface) {
            session_set_save_handler($this->defaultSessionHandler, true);
        }
    }
}
