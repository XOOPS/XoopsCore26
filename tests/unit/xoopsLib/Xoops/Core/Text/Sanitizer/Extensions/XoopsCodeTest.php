<?php
namespace Xoops\Core\Text\Sanitizer\Extensions;

use Xoops\Core\Text\Sanitizer;

require_once __DIR__.'/../../../../../../init_new.php';

class XoopsCodeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var XoopsCode
     */
    protected $object;

    /**
     * @var Sanitizer
     */
    protected $sanitizer;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->sanitizer = Sanitizer::getInstance();
        $this->object = new XoopsCode($this->sanitizer);
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
        $this->assertInstanceOf('\Xoops\Core\Text\Sanitizer\ExtensionAbstract', $this->object);
        $this->assertInstanceOf('\Xoops\Core\Text\Sanitizer\SanitizerComponent', $this->object);
        $this->assertInstanceOf('\Xoops\Core\Text\Sanitizer\SanitizerConfigurable', $this->object);
    }

    public function testRegisterExtensionProcessing()
    {
        $this->sanitizer->enableComponentForTesting('xoopscode');
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('siteurl'));
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('url'));
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('color'));
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('size'));
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('font'));
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('email'));
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('b'));
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('i'));
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('u'));
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('d'));
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('center'));
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('left'));
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('right'));
    }
}
