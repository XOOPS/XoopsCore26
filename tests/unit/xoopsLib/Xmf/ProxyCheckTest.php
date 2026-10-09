<?php
namespace Xmf\Test;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Xmf\ProxyCheck;

class LocalProxyCheck extends ProxyCheck
{
    public function __construct($name, $header)
    {
        $this->proxyHeaderName = $name;
        $this->proxyHeader = $header;
    }
}

#[CoversClass(ProxyCheck::class)]
class ProxyCheckTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProxyCheck
     */
    protected $object;

    /** @var array{0: bool, 1: bool, 2: mixed} whether xoopsConfig and proxy_env existed, and proxy_env's value */
    private $savedProxyEnv;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        // the real constructor reads this global; keep other tests' settings out of testGet()
        $this->savedProxyEnv = [
            \array_key_exists('xoopsConfig', $GLOBALS),
            isset($GLOBALS['xoopsConfig']) && \array_key_exists('proxy_env', $GLOBALS['xoopsConfig']),
            $GLOBALS['xoopsConfig']['proxy_env'] ?? null,
        ];
        unset($GLOBALS['xoopsConfig']['proxy_env']);
        $this->object = new ProxyCheck();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        // ProxyCheck's `global $xoopsConfig` creates the global, so drop it if it was not there before
        if (!$this->savedProxyEnv[0]) {
            unset($GLOBALS['xoopsConfig']);
        } elseif ($this->savedProxyEnv[1]) {
            $GLOBALS['xoopsConfig']['proxy_env'] = $this->savedProxyEnv[2];
        }
    }

    public function testGet()
    {
        $ip = $this->object->get();
        $this->assertFalse($ip);
    }

    public static function getProxyCheckTestData()
    {
        return array(
//          ['name', 'header', 'expected'],
            ['HTTP_FORWARDED', 'for=192.168.2.60;proto=http;by=203.0.113.43, for=192.0.2.43, for=198.51.100.17', false],
            ['HTTP_FORWARDED', 'for=203.0.113.195;proto=http;by=203.0.113.43, for=192.0.2.43, for=198.51.100.17', '203.0.113.195'],
            ['HTTP_FORWARDED', 'for="[2020:db8:85a3:8d3:1319:8a2e:370:7348]";proto=http;by=203.0.113.43', '2020:db8:85a3:8d3:1319:8a2e:370:7348'],
            ['HTTP_NOT_FORWARDED', 'for="[2020:db8:85a3:8d3:1319:8a2e:370:7348]";proto=http;by=203.0.113.43', false],
            ['HTTP_CLIENT_IP', '203.0.113.195, 70.41.3.18, 150.172.238.178', '203.0.113.195'],
            ['STUFF', '2020:db8:85a3:8d3:1319:8a2e:370:7348', '2020:db8:85a3:8d3:1319:8a2e:370:7348'],
        );
    }

    #[DataProvider('getProxyCheckTestData')]
    public function testProxyCheck($name, $header, $expected)
    {
        $obj = new LocalProxyCheck($name, $header);
        $this->assertSame($expected, $obj->get());
    }

}
