<?php
require_once(__DIR__.'/../../../init_new.php');

use Xoops\Auth\AuthAbstract;
use Xoops\Auth\Provisioning;
use Xoops\Core\Database\Factory;

class Xoops_Auth_ProvisioningTest_AuthAbstractInstance extends AuthAbstract
{
    function authenticate($uname, $pwd = null) {}
}

class Xoops_Auth_ProvisioningTest extends \PHPUnit\Framework\TestCase
{
    use DatabaseTestTrait;

    protected $object = null;

    public function setUp(): void
    {
        $this->requireDatabase();
        $conn = Factory::getConnection();
        $auth = new Xoops_Auth_ProvisioningTest_AuthAbstractInstance($conn);
        $this->object = new Provisioning($auth);
    }

    public function test___construct()
    {
        $instance = $this->object;
        $this->assertInstanceOf('\Xoops\Auth\Provisioning', $instance);
    }

    public function test___publicProperties()
    {
        $items = array('ldap_provisioning', 'ldap_provisioning_upd','ldap_field_mapping',
            'ldap_provisioning_group');
        foreach ($items as $item) {
            $prop = new ReflectionProperty($this->object, $item);
            $this->assertTrue($prop->isPublic());
        }
    }

    public function test_getInstance()
    {
        $conn = Factory::getConnection();
        $auth = new Xoops_Auth_ProvisioningTest_AuthAbstractInstance($conn);
        $instance = \Xoops\Auth\Provisioning::getInstance($auth);
        $instance2 = \Xoops\Auth\Provisioning::getInstance($auth);
        $this->assertSame($instance, $instance2);
    }

    public function test_getXoopsUser()
    {
        $instance = $this->object;

        $value = $instance->getXoopsUser('not_a_user');
        $this->assertFalse($value);

        $memberHandler = \Xoops::getInstance()->getHandlerMember();
        $userObject = $memberHandler->getUser(1);
        $userName = $userObject->getVar('uname');

        $value = $instance->getXoopsUser($userName);
        $this->assertInstanceOf('\Xoops\Core\Kernel\Handlers\XoopsUser', $value);
    }

    public function test_sync()
    {
        $instance = $this->object;

        $value = $instance->sync(array(), 'not_a_user');
        $this->assertFalse($value);

        $memberHandler = \Xoops::getInstance()->getHandlerMember();
        $userObject = $memberHandler->getUser(1);
        $userName = $userObject->getVar('uname');

        $value = $instance->sync(array(), $userName);
        $this->assertInstanceOf('\Xoops\Core\Kernel\Handlers\XoopsUser', $value);
    }

    public function test_setVarsMapping()
    {
        $this->markTestIncomplete();
    }

    public function test_add()
    {
        $this->markTestIncomplete();
    }

    public function test_change()
    {
        $this->markTestIncomplete();
    }

    public function test_delete()
    {
        $instance = $this->object;

        $instance->delete();
        $this->assertTrue(true); // always OK
    }

    public function test_suspend()
    {
        $instance = $this->object;

        $instance->suspend();
        $this->assertTrue(true); // always OK
    }

    public function test_restore()
    {
        $instance = $this->object;

        $instance->restore();
        $this->assertTrue(true); // always OK
    }

    public function test_resetpwd()
    {
        $instance = $this->object;

        $instance->resetpwd();
        $this->assertTrue(true); // always OK
    }
}
