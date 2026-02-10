<?php
require_once(__DIR__.'/../../../init_new.php');

class Xoops_Module_PluginTest extends \PHPUnit\Framework\TestCase
{
    use DatabaseTestTrait;

    protected $myClass = '\Xoops\Module\Plugin';

    public function setUp(): void
    {
        $this->requireDatabase();
    }
	
	public function test_getPlugin()
	{
        $instance = $this->myClass;
		$x = $instance::getPlugin('dummy');
		$this->assertSame(false, $x);
		
		$x = $instance::getPlugin('page');
		$this->assertInstanceOf('\Xoops\Module\Plugin\PluginAbstract', $x);
	}
	
	public function test_getPlugins()
	{
        $instance = $this->myClass;
		
		$x = $instance::getPlugins();
		$this->assertTrue(is_array($x));
	}

}
