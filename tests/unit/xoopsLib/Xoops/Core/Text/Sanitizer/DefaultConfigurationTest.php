<?php
namespace Xoops\Core\Text\Sanitizer;

require_once __DIR__.'/../../../../../init_new.php';

class DefaultConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DefaultConfiguration
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new DefaultConfiguration;
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
        $this->assertInstanceOf('\Xoops\Core\Text\Sanitizer\ConfigurationAbstract', $this->object);
        $this->assertInstanceOf('\Xoops\Core\AttributeInterface', $this->object);
        $this->assertInstanceOf('\ArrayObject', $this->object);
    }

    public function testBuildDefaultConfiguration()
    {
        $defaultConfig = $this->object->buildDefaultConfiguration();
        $this->assertTrue(is_array($defaultConfig));
        $this->assertArrayHasKey('sanitizer', $defaultConfig);
        $this->assertArrayHasKey('xoopscode', $defaultConfig);
    }
}
