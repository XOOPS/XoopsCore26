<?php
namespace Xmf;

require_once(__DIR__.'/../../init_new.php');

class LanguageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Language
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new Language;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testTranslate()
    {
        $str = 'string';
        $x = Language::translate($str);
        $this->assertSame($str, $x);
    }

    public function testLoad()
    {
        $this->assertTrue(Language::load('xmf'));

        $this->assertFalse(Language::load('xmfblahblahblah'));

        $this->assertFalse(Language::load('xmf/Program Files/stuff'));
    }

    public function testLoadException()
    {
        $str = "Test\0Test";
        $this->expectException(\InvalidArgumentException::class);
        $x = Language::load($str);
    }
}
