<?php
namespace Xoops\Core\Theme;

require_once __DIR__.'/../../../../init_new.php';

class NullThemeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var NullTheme
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new NullTheme;
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
        $this->assertInstanceOf('\Xoops\Core\Theme\XoopsTheme', $this->object);
    }

    public function testXoInit()
    {
        $this->assertTrue($this->object->xoInit());
    }

    public function testRender()
    {
        $this->assertTrue($this->object->render());
    }

    public function testAddStylesheet()
    {
        $this->assertNull($this->object->addStylesheet());
    }

    public function testAddScriptAssets()
    {
        $this->assertNull($this->object->addScriptAssets(''));
    }

    public function testAddStylesheetAssets()
    {
        $this->assertNull($this->object->addStylesheetAssets(''));
    }

    public function testAddBaseAssets()
    {
        $this->assertNull($this->object->addBaseAssets('', ''));
    }

    public function testAddBaseScriptAssets()
    {
        $this->assertNull($this->object->addBaseScriptAssets(''));
    }

    public function testAddBaseStylesheetAssets()
    {
        $this->assertNull($this->object->addBaseStylesheetAssets(''));
    }

    public function testSetNamedAsset()
    {
        $this->assertNull($this->object->setNamedAsset('', ''));
    }
}
