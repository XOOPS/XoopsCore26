<?php
namespace Xmf\Test;

use Xmf\IPAddress;

class IPAddressTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var IPAddress
     */
    protected $object;

    /**
     * @var IPAddress
     */
    protected $objectV6;

    /** @var string ip address of setUp object */
    protected $testIPV4 = '192.168.20.12';

    /** @var string ip address of setUp object */
    protected $testIPV6 = '3ffe:2a00:100:7031::1';

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new IPAddress($this->testIPV4);
        $this->objectV6 = new IPAddress($this->testIPV6);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testFromRequest()
    {
        $testAddress = '192.168.20.16';
        $_SERVER['REMOTE_ADDR'] = $testAddress;
        $instance = IPAddress::fromRequest();
        $actual = $instance->asReadable();
        $this->assertEquals($testAddress, $actual);

        $testAddress = '3ffe:2a00:100:7031::1';
        $_SERVER['REMOTE_ADDR'] = $testAddress;
        $instance = IPAddress::fromRequest();
        $actual = $instance->asReadable();
        $this->assertEquals($testAddress, $actual);
    }

    public function testAsReadable()
    {
        $this->assertEquals($this->testIPV4, $this->object->asReadable());
        $this->assertEquals($this->testIPV6, $this->objectV6->asReadable());
    }

    public function testAsBinary()
    {
        $addressV6 = '3031:3233:3435:3637:3839:584F:4F50:5334';
        $instanceV6 = new IPAddress($addressV6);
        $this->assertEquals($instanceV6->asBinary(), '0123456789XOOPS4');

        $addressV4 = '67.48.68.69';
        $instanceV4 = new IPAddress($addressV4);
        $this->assertEquals($instanceV4->asBinary(), 'C0DE');
    }

    public function testIpVersion()
    {
        $this->assertSame(4, $this->object->ipVersion());
        $this->assertSame(6, $this->objectV6->ipVersion());

        $instance = new IPAddress('garbage');
        $this->assertFalse($instance->ipVersion());
    }

    public function testSameSubnet()
    {
        $instanceV6 = new IPAddress('FE80:0000:0000:0000:0202:B3FF:FE1E:8329');
        $addressV6 = 'FE80:0000:0000:0000:8000:0000:0000:0000';

        $this->assertFalse($instanceV6->sameSubnet($this->testIPV4, 16, 64));
        $this->assertTrue($instanceV6->sameSubnet($addressV6, 14, 56));
        $this->assertTrue($instanceV6->sameSubnet($addressV6, 16, 64));
        $this->assertFalse($instanceV6->sameSubnet($addressV6, 17, 65));
        $this->assertTrue($instanceV6->sameSubnet($instanceV6->asReadable(), 32, 128));

        $instanceV4 = new IPAddress('255.255.255.1');
        $addressV4 = '255.255.255.129';
        $this->assertFalse($instanceV4->sameSubnet($instanceV6->asReadable(), 1, 1));
        $this->assertTrue($instanceV4->sameSubnet($addressV4, 8, 32));
        $this->assertTrue($instanceV4->sameSubnet($addressV4, 16, 64));
        $this->assertTrue($instanceV4->sameSubnet($addressV4, 24, 96));
        $this->assertFalse($instanceV4->sameSubnet($addressV6, 25, 98));
        $this->assertTrue($instanceV4->sameSubnet($instanceV4->asReadable(), 32, 128));
    }
}
