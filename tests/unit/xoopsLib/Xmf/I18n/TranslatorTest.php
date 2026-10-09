<?php

declare(strict_types=1);

namespace Xmf\Test\I18n;

use Xmf\I18n\Translator;

class TranslatorTest extends \PHPUnit\Framework\TestCase
{
    public function testTReturnsConstantValueWhenDefined(): void
    {
        if (!\defined('_TEST_TRANSLATOR_LABEL')) {
            \define('_TEST_TRANSLATOR_LABEL', 'Translated Value');
        }
        $this->assertSame('Translated Value', Translator::t('_TEST_TRANSLATOR_LABEL'));
    }

    public function testTReturnsLabelWhenConstantNotDefined(): void
    {
        $this->assertSame('_UNDEFINED_CONST_XYZ', Translator::t('_UNDEFINED_CONST_XYZ'));
    }

    public function testTReturnsLabelWhenNotMatchingPattern(): void
    {
        // Does not start with underscore
        $this->assertSame('NOUNDERSCORE', Translator::t('NOUNDERSCORE'));

        // Lowercase characters
        $this->assertSame('_lowercase', Translator::t('_lowercase'));

        // Empty string
        $this->assertSame('', Translator::t(''));

        // Just an underscore
        $this->assertSame('_', Translator::t('_'));
    }

    public function testTReturnsLabelForNonStringConstant(): void
    {
        if (!\defined('_TEST_TRANSLATOR_INT')) {
            \define('_TEST_TRANSLATOR_INT', 42);
        }
        $this->assertSame('_TEST_TRANSLATOR_INT', Translator::t('_TEST_TRANSLATOR_INT'));
    }

    public function testTHandlesValidPatternFormats(): void
    {
        if (!\defined('_MI_MYMODULE_NAME')) {
            \define('_MI_MYMODULE_NAME', 'My Module');
        }
        $this->assertSame('My Module', Translator::t('_MI_MYMODULE_NAME'));
    }
}
