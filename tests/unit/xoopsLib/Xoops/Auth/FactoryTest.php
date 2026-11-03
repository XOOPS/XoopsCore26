<?php
require_once(__DIR__.'/../../../init_new.php');

class Xoops_Auth_FactoryTest extends \PHPUnit\Framework\TestCase
{
    protected $myClass = 'Xoops\Auth\Factory';

    /** @var array<string, mixed> */
    private $savedConfig = [];

    protected function setUp(): void
    {
        $xoops = \Xoops::getInstance();
        foreach (['auth_method', 'ldap_users_bypass'] as $name) {
            $this->savedConfig[$name] = $xoops->getConfig($name);
        }
    }

    protected function tearDown(): void
    {
        $xoops = \Xoops::getInstance();
        foreach ($this->savedConfig as $name => $value) {
            $xoops->setConfig($name, $value);
        }
    }

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

	public function test_getAuthConnectionBypassIgnoresDomainAndCase()
	{
		$class = $this->myClass;

		$xoops = \Xoops::getInstance();
		$xoops->setConfig('auth_method', 'ads');
		$xoops->setConfig('ldap_users_bypass', array('admin'));

		// AD strips the domain and the user lookup ignores case, so both still name 'admin'
		$this->assertInstanceOf('Xoops\Auth\Xoops', $class::getAuthConnection('CORP\\admin', true));
		$this->assertInstanceOf('Xoops\Auth\Xoops', $class::getAuthConnection('Admin', true));
	}

	public function test_getAuthConnectionOtherDomainUserUsesDirectory()
	{
		$class = $this->myClass;
		if (!extension_loaded('ldap')) {
			$this->markTestSkipped('The directory backends need the ldap extension.');
		}

		$xoops = \Xoops::getInstance();
		$xoops->setConfig('ldap_users_bypass', array('admin'));

		$xoops->setConfig('auth_method', 'ads');
		$this->assertInstanceOf('Xoops\Auth\Ads', $class::getAuthConnection('CORP\\bob', true));

		// plain LDAP looks the qualified name up as given, so it is a different directory account
		$xoops->setConfig('auth_method', 'ldap');
		$this->assertInstanceOf('Xoops\Auth\Ldap', $class::getAuthConnection('CORP\\admin', true));
		$this->assertInstanceOf('Xoops\Auth\Xoops', $class::getAuthConnection('Admin', true));
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
