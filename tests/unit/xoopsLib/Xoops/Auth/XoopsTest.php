<?php
require_once(__DIR__.'/../../../init_new.php');

class Xoops_Auth_XoopsTest extends \PHPUnit\Framework\TestCase
{
    use DatabaseTestTrait;

    protected $myClass = 'Xoops\Auth\Xoops';

    public function setUp(): void
    {
        $this->requireDatabase();
    }

    public function test___construct()
	{
		$conn = \Xoops\Core\Database\Factory::getConnection();

		$instance = new $this->myClass($conn);
		$this->assertInstanceOf($this->myClass, $instance);
		$this->assertInstanceOf('Xoops\Auth\AuthAbstract', $instance);
    }

	public function test_authenticate()
	{
		$conn = \Xoops\Core\Database\Factory::getConnection();

		$instance = new $this->myClass($conn);

		$uname = 'admin';
		$pwd = 'pwd';
		$value = $instance->authenticate($uname, $pwd);
		$this->assertSame(false, $value);
	}
}
