<?php
namespace Xoops\Test\Core\Locale;

use Xoops\Core\Locale\LegacyCodes;

class LegacyCodesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var LegacyCodes
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new LegacyCodes;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testGetLegacyNameSingle()
    {
        $languageArray = LegacyCodes::getLegacyName('fr_FR');
        $this->assertTrue(is_array($languageArray));
        $this->assertEquals(1, count($languageArray));
        $this->assertTrue(in_array('french', $languageArray, true));
    }

    public function testGetLegacyNameNone()
    {
        $languageArray = LegacyCodes::getLegacyName('xx_XX');
        $this->assertTrue(is_array($languageArray));
        $this->assertEquals(0, count($languageArray));
    }

    public function testGetLegacyNameMultiple()
    {
        $languageArray = LegacyCodes::getLegacyName('pt_BR');
        $this->assertTrue(is_array($languageArray));
        $this->assertEquals(2, count($languageArray));
        $this->assertTrue(in_array('portuguesebr', $languageArray, true));
        $this->assertTrue(in_array('brazilian', $languageArray, true));
    }

    public function testGetLegacyNameByShort()
    {
        $languageArray = LegacyCodes::getLegacyName('zh_Hans');
        $this->assertTrue(in_array('schinese', $languageArray, true));
    }

    public function testGetLegacyNameByFull()
    {
        $languageArray = LegacyCodes::getLegacyName('zh-Hans-CN');
        $this->assertTrue(in_array('schinese', $languageArray, true));
    }

    public function testGetLocaleCode()
    {
        $this->assertEquals('en-Latn-US', LegacyCodes::getLocaleCode('english'));
        $this->assertEquals('zh-Hant-TW', LegacyCodes::getLocaleCode('chinese_zh'));
        $this->assertNull(LegacyCodes::getLocaleCode('piglatin'));
    }
}
