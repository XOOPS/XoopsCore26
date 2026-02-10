<?php
require_once(__DIR__.'/../init_new.php');

class TemplateTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = 'XoopsTpl';

    /**
     * @var XoopsTpl
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $xoops = \Xoops::getInstance();
        \XoopsLoad::loadFile($xoops->path('class/template.php'));
        $this->object = new XoopsTpl();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testContracts()
    {
        $this->assertInstanceOf('\XoopsTpl', $this->object);
        $this->assertInstanceOf('\Xoops\Core\XoopsTpl', $this->object);
        $this->assertInstanceOf('\Smarty', $this->object);
    }
}
