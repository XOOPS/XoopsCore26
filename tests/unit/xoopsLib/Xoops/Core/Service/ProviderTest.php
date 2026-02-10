<?php
require_once(__DIR__.'/../../../../init_new.php');
require_once(XOOPS_TU_ROOT_PATH.'/modules/avatars/class/AvatarsProvider.php');

use Xoops\Core\Service\Manager;
use Xoops\Core\Service\Provider;
use Xoops\Core\Kernel\Handlers\XoopsUser;

class ProviderTest extends \PHPUnit\Framework\TestCase
{
    protected $service = 'Avatar';

    /**
     * @var Provider
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $manager = Manager::getInstance();
        $this->object = new Provider($manager, $this->service);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testGetProviderMode()
    {
        $instance = $this->object;

        $result = $instance->getProviderMode();
        $this->assertSame(Manager::MODE_EXCLUSIVE, $result);
    }

    public function testSortProviders()
    {
        $instance = $this->object;

        $provider1 = new AvatarsProvider();
        $provider1->setPriority(10);
        $instance->register($provider1);

        $provider2 = new AvatarsProvider();
        $provider2->setPriority(5);
        $instance->register($provider2);

        $instance->sortProviders();

        $result = $instance->getRegistered();
        $this->assertSame($provider2, $result[0]);
        $this->assertSame(5, $result[0]->getPriority());
        $this->assertSame($provider1, $result[1]);
        $this->assertSame(10, $result[1]->getPriority());
    }

    public function testIsAvailable()
    {
        $instance = $this->object;

        $result = $instance->isAvailable();
        $this->assertTrue($result);
    }

    public function test__call()
    {
        $instance = $this->object;

        $provider = new AvatarsProvider();
        $instance->register($provider);

        $user = new XoopsUser();
        $result = $instance->getAvatarEditUrl($user);
        $this->assertInstanceOf('\Xoops\Core\Service\Response',$result);
        $this->assertTrue($result->isSuccess());

        $result = $instance->getAvatarUrl($provider);
        $this->assertInstanceOf('\Xoops\Core\Service\Response',$result);
        $this->assertFalse($result->isSuccess());
    }

    public function test__callStatic()
    {
        $result = Provider::staticDummyMethod();
        $this->assertTrue(is_null($result));
    }
}
