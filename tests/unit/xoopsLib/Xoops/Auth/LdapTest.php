<?php
require_once(__DIR__.'/../../../init_new.php');

class Xoops_Auth_LdapTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = 'Xoops\Auth\Ldap';

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
		if (!extension_loaded('ldap')) $this->markTestSkipped();
    }

    public function testContract()
	{
		$instance = new $this->myclass();
		$this->assertInstanceOf($this->myclass, $instance);
		$this->assertInstanceOf('\Xoops\Auth\AuthAbstract', $instance);
    }

	public function test_cp1252_to_utf8()
	{
		$this->markTestIncomplete();
	}

	public function test_authenticate()
	{
		$this->markTestIncomplete();
	}

	public function test_getUserDN()
	{
		$this->markTestIncomplete();
	}

	public function test_getFilter()
	{
		$this->markTestIncomplete();
	}

	public function test_loadXoopsUser()
	{
		$this->markTestIncomplete();
	}
}
