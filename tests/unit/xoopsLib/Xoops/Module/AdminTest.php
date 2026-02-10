<?php

use Xoops\Core\XoopsTpl;

require_once(__DIR__.'/../../../init_new.php');

class ModuleadminTest extends \PHPUnit\Framework\TestCase
{
    protected $myClass = '\Xoops\Module\Admin';
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->markTestSkipped('side effects');
    }


    public function test___construct()
	{
        $level = ob_get_level();
        $instance = new $this->myClass();
        while (ob_get_level() > $level) @ob_end_flush();
        $this->assertInstanceOf($this->myClass, $instance);
    }

    public function test_addBreadcrumbLink()
	{
        $level = ob_get_level();
        $xoops = Xoops::getInstance();
        $theme_factory=new \Xoops\Core\Theme\Factory();
        $theme=$theme_factory->createInstance();
        $xoops->setTheme($theme);
        $template=new XoopsTpl();
        $xoops->setTpl($template);
        $instance = new $this->myClass();
        $instance->addBreadcrumbLink();
        $x = $instance->renderBreadcrumb();
        while (ob_get_level() > $level) @ob_end_flush();
        $x = str_replace("\r\n","\n",$x);
		$this->assertSame("<ul class=\"breadcrumb\">\n    </ul>", $x);
    }

}
