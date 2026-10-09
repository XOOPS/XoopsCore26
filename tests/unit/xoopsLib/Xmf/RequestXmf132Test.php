<?php

declare(strict_types=1);

namespace Xmf\Test;

use PHPUnit\Framework\Attributes\CoversClass;
use Xmf\Request;

/**
 * Behaviour added to Xmf\Request in XMF 1.3.x
 */
#[CoversClass(Request::class)]
class RequestXmf132Test extends \PHPUnit\Framework\TestCase
{
    /** @var array<string, array<string, mixed>> superglobals as they were before the test */
    private $saved;

    protected function setUp(): void
    {
        $this->saved = ['GET' => $_GET, 'POST' => $_POST, 'REQUEST' => $_REQUEST, 'ENV' => $_ENV, 'SERVER' => $_SERVER];
        $_GET = $_POST = $_REQUEST = [];
    }

    protected function tearDown(): void
    {
        $_GET = $this->saved['GET'];
        $_POST = $this->saved['POST'];
        $_REQUEST = $this->saved['REQUEST'];
        $_ENV = $this->saved['ENV'];
        $_SERVER = $this->saved['SERVER'];
    }

    public function testMethodHashFollowsRequestMethod(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['x'] = ' posted ';
        $_GET['x'] = 'got';

        $this->assertSame('posted', Request::getVar('x', null, 'method'));
        $this->assertTrue(Request::hasVar('x', 'method'));
        $this->assertSame(['x' => 'posted'], Request::get('method'));

        $this->assertSame('posted', Request::setVar('x', 'changed', 'method'));
        $this->assertSame('changed', $_POST['x']);
        $this->assertSame('changed', $_REQUEST['x']);
    }

    public function testDefaultIsCleanedLikeInput(): void
    {
        $this->assertSame('padded', Request::getVar('missing', '  padded  '));
        $this->assertSame(['solo'], Request::getVar('missing', 'solo', 'default', 'array'));
        $this->assertNull(Request::getVar('missing'));
    }

    public function testNullInputFallsBackToDefault(): void
    {
        $_REQUEST['n'] = null;

        $this->assertSame('fallback', Request::getVar('n', 'fallback'));
    }

    public function testTypedGettersCastTheirResult(): void
    {
        $_REQUEST['i'] = '42abc';
        $_REQUEST['f'] = '1.5';
        $_REQUEST['b'] = '1';

        $this->assertSame(42, Request::getInt('i'));
        $this->assertSame(7, Request::getInt('missing', 7));
        $this->assertSame(1.5, Request::getFloat('f'));
        $this->assertTrue(Request::getBool('b'));
        $this->assertFalse(Request::getBool('missing'));
    }



    public function testSetVarWritesEnvAndServer(): void
    {
        $this->assertNull(Request::setVar('XMF_TEST_ENV', 'env', 'env'));
        $this->assertSame('env', $_ENV['XMF_TEST_ENV']);

        Request::setVar('XMF_TEST_SERVER', 'srv', 'server');
        $this->assertSame('srv', $_SERVER['XMF_TEST_SERVER']);
        $this->assertSame('srv', Request::setVar('XMF_TEST_SERVER', 'kept', 'server', false));
        $this->assertSame('srv', $_SERVER['XMF_TEST_SERVER']);
    }

}
