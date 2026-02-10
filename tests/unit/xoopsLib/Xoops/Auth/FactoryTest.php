<?php
require_once(__DIR__.'/../../../init_new.php');

class Xoops_Auth_FactoryTest extends \PHPUnit\Framework\TestCase
{
    protected $myClass = 'Xoops\Auth\Factory';

    public function test___construct()
    {
        $instance = new $this->myClass();
        $this->assertInstanceOf($this->myClass, $instance);
    }

	public function test_getAuthConnection()
	{
		$class = $this->myClass;

		$xoops = \Xoops::getInstance();

		$uname = '';
		$instance = $class::getAuthConnection($uname);
		$this->assertInstanceOf('Xoops\Auth\Xoops', $instance);
	}

    /**
     *
     */
	public function test_getAuthConnection100()
	{
		$class = $this->myClass;
		if (!extension_loaded('ldap')) $this->markTestSkipped();

		$xoops = \Xoops::getInstance();
		$xoops->setConfig('auth_method', 'ads');

		$uname = '';
		$instance = $class::getAuthConnection($uname, true);
		$this->assertInstanceOf('Xoops\Auth\Ads', $instance);

	}

	public function test_getAuthConnection150()
	{
		$class = $this->myClass;
		if (!extension_loaded('ldap')) $this->markTestSkipped();

		$xoops = \Xoops::getInstance();
		$xoops->setConfig('auth_method', 'ads');
		$xoops->setConfig('ldap_users_bypass', array('admin'));

		$uname = 'admin';
		$instance = $class::getAuthConnection($uname, true);
		$this->assertInstanceOf('Xoops\Auth\Xoops', $instance);
	}

    /**
     *
     */
	public function test_getAuthConnection200()
	{
		$class = $this->myClass;
		if (!extension_loaded('ldap')) $this->markTestSkipped();

		$xoops = \Xoops::getInstance();

		$xoops->setConfig('auth_method', 'ldap');

		$uname = '';
		$instance = $class::getAuthConnection($uname, true);
		$this->assertInstanceOf('Xoops\Auth\Ldap', $instance);

	}

	public function test_getAuthConnection250()
	{
		$class = $this->myClass;
		if (!extension_loaded('ldap')) $this->markTestSkipped();

		$xoops = \Xoops::getInstance();

		$xoops->setConfig('auth_method', 'ldap');
		$xoops->setConfig('ldap_users_bypass', array('admin'));

		$uname = 'admin';
		$instance = $class::getAuthConnection($uname, true);
		$this->assertInstanceOf('Xoops\Auth\Xoops', $instance);

	}

}
