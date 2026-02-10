<?php
require_once(__DIR__.'/../init_new.php');

class ThemeBlocksTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var XoopsThemeBlocksPlugin
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new XoopsThemeBlocksPlugin;
    }

    public function testContracts()
    {
        $this->assertInstanceOf('\Xoops\Core\Theme\PluginAbstract', $this->object);
    }
}
