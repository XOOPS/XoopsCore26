<?php
require_once(__DIR__.'/../../../../init_new.php');

class Xoops_Module_Plugin_AbstractTestInstance extends Xoops\Module\Plugin\PluginAbstract
{
}

class Xoops_Module_Plugin_AbstractTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = 'Xoops_Module_Plugin_AbstractTestInstance';

    public function test___construct()
	{
        $xoops_root_path = \XoopsBaseConfig::get('root-path');
		$dir = $xoops_root_path.'/modules/avatar';
		$instance = new $this->myclass($dir);
		$this->assertInstanceOf($this->myclass, $instance);
    }

}
