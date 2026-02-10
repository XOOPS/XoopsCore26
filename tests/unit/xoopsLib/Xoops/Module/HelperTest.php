<?php
require_once(__DIR__.'/../../../init_new.php');

class Xoops_Module_HelperTest extends \PHPUnit\Framework\TestCase
{
    use DatabaseTestTrait;

    protected $myClass = '\Xoops\Module\Helper';

    public function setUp(): void
    {
        $this->requireDatabase();
    }
	
	public function test_getHelper()
	{
        $instance = $this->myClass;
		$x = $instance::getHelper();
		$this->assertInstanceOf('\Xoops\Module\Helper\Dummy', $x);
	}

}
