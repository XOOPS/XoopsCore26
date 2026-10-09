<?php
namespace Xmf\Test;

use PHPUnit\Framework\Attributes\DataProvider;
use Xmf\FilterInput;

class FilterInputTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FilterInput
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = FilterInput::getInstance();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testGetInstance()
    {
        $this->assertInstanceOf('\Xmf\FilterInput', $this->object);

        $instance = FilterInput::getInstance();
        $this->assertSame($instance, $this->object);

        $instance = FilterInput::getInstance(array(), array(), 0, 0, 0);
        $this->assertNotSame($instance, $this->object);
    }

    public function testProcess()
    {
        $input = 'Lorem ipsum </i><script>alert();</script>';
        $expected = 'Lorem ipsum alert();';
        $this->assertEquals($expected, $this->object->process($input));

        $input = 'Lorem ipsum';
        $this->assertEquals($input, $this->object->process($input));
    }

    public function testClean()
    {
        $input = 'Lorem ipsum </i><script>alert();</script>';
        $expected = 'Lorem ipsum alert();';
        $this->assertEquals($expected, FilterInput::clean($input, 'string'));

        $input = 'Lorem ipsum &#x3C;&#x73;&#x63;&#x72;&#x69;&#x70;&#x74;&#x3E;&#x61;&#x6C;&#x65;&#x72;&#x74;&#x28;&#x29;&#x3B;&#x3C;&#x2F;&#x73;&#x63;&#x72;&#x69;&#x70;&#x74;&#x3E;';
        $expected = 'Lorem ipsum alert();';
        $this->assertEquals($expected, FilterInput::clean($input, 'string'), FilterInput::clean($input, 'string'));

        $input = 'Lorem ipsum';
        $expected = $input;
        $this->assertEquals($expected, FilterInput::clean($input, 'string'));
    }

    public function testCleanVarDefault()
    {
        $filter = FilterInput::getInstance();
        $safeTest = '<p>This is a <em>simple</em> test.</p>';
        $this->assertEquals('This is a simple test.', $filter->cleanVar($safeTest));
    }

    public function testCleanVarFilter()
    {
        $filter = FilterInput::getInstance(array(), array(), 1, 1);

        $safeTest = '<p>This is a <em>simple</em> test.</p>';
        $this->assertEquals($safeTest, $filter->cleanVar($safeTest));
    }

    public function testCleanVarFilterXss()
    {
        $filter = FilterInput::getInstance(array(), array(), 1, 1);

        $xssTest = '<p>This is a <em>xss</em> <script>alert();</script> test.</p>';
        $xssTestExpect = '<p>This is a <em>xss</em> alert(); test.</p>';
        $this->assertEquals($xssTestExpect, $filter->cleanVar($xssTest));
    }

    public static function getTestForCleanVarType()
    {
        return array(
            array('100', 'int', 100),
            array('100', 'INTEGER', 100),
            array('55.1', 'FLOAT', 55.1),
            array('55.1', 'DOUBLE', 55.1),
            array('1', 'BOOL', true),
            array('0', 'BOOLEAN', false),
            array('Value', 'WORD', 'Value'),
            array('Alpha99', 'ALPHANUM', 'Alpha99'),
            array('Alpha99', 'ALNUM', 'Alpha99'),
            array('value', 'ARRAY', array('value')),
//          ['value', 'type', 'expected'],
        );
    }

    #[DataProvider('getTestForCleanVarType')]
    public function testCleanVarTypes($value, $type, $expected)
    {
        $this->assertSame($expected, $this->object->cleanVar($value, $type));
    }

    // ported from XMF 1.3.2 tests/unit
    /**
     * Tag names that are empty or do not start with a letter are rejected by the
     * tag-name regex, so they are stripped. Locks in the removal of the redundant
     * "!$tagName" test in filterTags().
     */
    public function testCleanStripsMalformedTagNames()
    {
        // "<>" is not recognised as a tag at all, so it is left untouched.
        $this->assertEquals('<>', FilterInput::clean('<>', 'string'));
        // A digit-led tag name fails the regex and is stripped.
        $this->assertEquals('', FilterInput::clean('<0>', 'string'));
        $this->assertEquals('hello', FilterInput::clean('<0>hello', 'string'));
        $this->assertEquals('text', FilterInput::clean('<0img src=x>text', 'string'));
        // A well-formed tag is still stripped, content preserved.
        $this->assertEquals('keep', FilterInput::clean('<img src=x>keep', 'string'));
    }

    public function testWeburlRejectsProtocolRelativeUrl()
    {
        $result = FilterInput::clean('//evil.example/path', 'WEBURL');
        $this->assertSame('', $result);
    }

    public function testWeburlAllowsHttpUrl()
    {
        $result = FilterInput::clean('http://example.com/page', 'WEBURL');
        $this->assertSame('http://example.com/page', $result);
    }

    public function testWeburlAllowsRelativeUrl()
    {
        $result = FilterInput::clean('/local/path', 'WEBURL');
        $this->assertSame('/local/path', $result);
    }

    public function testWeburlRejectsProtocolRelativeUrlWithLeadingWhitespace()
    {
        $result = FilterInput::clean('  //evil.example/path', 'WEBURL');
        $this->assertSame('', $result);
    }

    public function testWeburlRejectsJavascriptScheme()
    {
        $result = FilterInput::clean('javascript:alert(1)', 'WEBURL');
        $this->assertSame('', $result);
    }

    public function testHexEntityDecode()
    {
        // &#x41; = 'A', &#x42; = 'B', &#x43; = 'C'
        $input = '&#x41;&#x42;&#x43;';
        $result = FilterInput::clean($input, 'string');
        $this->assertStringContainsString('ABC', $result);
    }

    public function testDecimalEntityDecode()
    {
        // &#65; = 'A', &#66; = 'B'
        $input = '&#65;&#66;';
        $result = FilterInput::clean($input, 'string');
        $this->assertStringContainsString('AB', $result);
    }

    public function testEmptyTagIsKeptWithoutGrowing()
    {
        // "<>" stays as text; it used to re-append the remainder on every pass, so remove() never settled
        $this->assertSame('a<>b', FilterInput::clean('a<>b', 'string'));
        $this->assertSame('x<> y<>', FilterInput::clean('x<> y<>', 'string'));
        $this->assertSame('<>keep', FilterInput::clean('<><b>keep</b>', 'string'));
    }

    public function testIncompleteTagDoesNotSurviveAsTag()
    {
        // text before a nested or missing ">" is kept, but without a "<" that would open a tag
        $this->assertSame('img src="<>" onerror=alert(1)>', FilterInput::clean('<img src="<>" onerror=alert(1)>', 'string'));
        $this->assertSame('img src=x onerror=alert(1) ', FilterInput::clean('<img src=x onerror=alert(1) <b>', 'string'));
        $this->assertSame('ximg src=x onerror=alert(1) ', FilterInput::clean('x<img src=x onerror=alert(1) ', 'string'));
        $this->assertSame('<>', FilterInput::clean('<><script>', 'string'));
        // a "<" that cannot open a tag stays
        $this->assertSame('a < b', FilterInput::clean('a < b', 'string'));
    }

    public function testAllowedHtmlBareTagTerminates()
    {
        // "/" of the reformatted "<a />" used to pass the attribute-name check and grow on every pass
        $filter = FilterInput::getInstance([], [], 1, 1);
        $this->assertSame('<a />', $filter->cleanVar('<a>', 'html'));
        $this->assertSame('<a href="x">t</a>', $filter->cleanVar('<a href="x" onclick="y">t</a>', 'html'));
    }

    public function testAllowedHtmlDropsEventHandlersInAnyCase()
    {
        $filter = FilterInput::getInstance([], [], 1, 1);
        $this->assertSame('<img src="x" />', $filter->cleanVar('<img src=x ONERROR=alert(1)>', 'html'));
    }

    public function testAllowedHtmlDropsEntityEncodedScriptUrls()
    {
        // the scheme check reads the value as a browser decodes it
        $filter = FilterInput::getInstance([], [], 1, 1);
        $this->assertSame('<a>x</a>', $filter->cleanVar('<a href="javascript&colon;alert(1)">x</a>', 'html'));
        $this->assertSame('<a>x</a>', $filter->cleanVar('<a href="java&Tab;script:alert(1)">x</a>', 'html'));
        $this->assertSame('<a href="https://example.com/recipes">x</a>', $filter->cleanVar('<a href="https://example.com/recipes">x</a>', 'html'));
    }

    public function testRemoveDropsAValueThatNeverSettles()
    {
        // a filterTags() that always changes its output must not loop without bound
        $filter = new class () extends FilterInput {
            public function __construct()
            {
                parent::__construct();
            }

            protected function filterTags($source)
            {
                return $source . 'x';
            }

            public function runRemove($source)
            {
                return $this->remove($source);
            }
        };
        $this->assertSame('', $filter->runRemove('a'));
    }

    public function testNamesWithATrailingNewlineAreRejected()
    {
        // "$" also matches before a final "\n", so "script\n" passed the name check and missed the blacklist
        $filter = FilterInput::getInstance([], [], 1, 1);
        $this->assertSame('alert(1)', $filter->cleanVar("<script\n>alert(1)</script\n>", 'html'));
        $this->assertSame('<form />', $filter->cleanVar("<form action\n=\"https://evil.example\">", 'html'));
        $this->assertStringNotContainsString("\n", FilterInput::clean("https://example.com/a\n", 'weburl'));
    }
}
