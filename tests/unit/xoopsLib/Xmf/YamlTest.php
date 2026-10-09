<?php
namespace Xmf\Test;

use Xmf\Yaml;

class YamlTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testDumpAndLoad()
    {
        $inputArray = array('one' => 1, 'two' => array(1,2), 'three' => '');

        $string = Yaml::dump($inputArray);
        $this->assertTrue(!empty($string));
        $this->assertTrue(is_string($string));

        $outputArray = Yaml::load((string) $string);
        $this->assertTrue(is_array($outputArray));
        $this->assertSame($inputArray, $outputArray);
    }

    public function testSaveAndRead()
    {
        $tmpfname = tempnam(sys_get_temp_dir(), 'TEST');
        $inputArray = array('one' => 1, 'two' => array(1,2), 'three' => '');

        $byteCount = Yaml::save($inputArray, $tmpfname);
        $this->assertFalse($byteCount === false);
        $this->assertGreaterThan(0, $byteCount);

        $outputArray = Yaml::read($tmpfname);
        $this->assertTrue(is_array($outputArray));
        $this->assertSame($inputArray, $outputArray);

        unlink($tmpfname);
    }

    public function testDumpAndLoadWrapped()
    {
        $inputArray = array('one' => 1, 'two' => array(1,2), 'three' => '');

        $string = Yaml::dumpWrapped($inputArray);
        $this->assertTrue(!empty($string));
        $this->assertTrue(is_string($string));

        $outputArray = Yaml::loadWrapped((string) $string);
        $this->assertTrue(is_array($outputArray));
        $this->assertSame($inputArray, $outputArray);
    }

    public function testDumpAndLoadWrappedStress()
    {
        $inputArray = array('start' => '---', 'end' => '...', 'misc' => 'stuff');

        $string = Yaml::dumpWrapped($inputArray);
        $this->assertTrue(!empty($string));
        $this->assertTrue(is_string($string));

        $outputArray = Yaml::loadWrapped((string) $string);
        $this->assertTrue(is_array($outputArray));
        $this->assertSame($inputArray, $outputArray);
    }

    public function testDumpAndLoadWrappedStress2()
    {
        $inputArray = array('start' => '---', 'end' => '...', 'misc' => 'stuff');

        $string = Yaml::dump($inputArray);
        $this->assertTrue(!empty($string));
        $this->assertTrue(is_string($string));

        $outputArray = Yaml::loadWrapped((string) $string);
        $this->assertTrue(is_array($outputArray));
        $this->assertSame($inputArray, $outputArray);
    }

    public function testSaveAndReadWrapped()
    {
        $tmpfname = tempnam(sys_get_temp_dir(), 'TEST');
        $inputArray = array('one' => 1, 'two' => array(1,2), 'three' => '');

        $byteCount = Yaml::saveWrapped($inputArray, $tmpfname);
        $this->assertFalse($byteCount === false);
        $this->assertGreaterThan(0, $byteCount);

        $outputArray = Yaml::readWrapped($tmpfname);
        $this->assertTrue(is_array($outputArray));
        $this->assertSame($inputArray, $outputArray);

        unlink($tmpfname);
    }

    // ported from XMF 1.3.2 tests/unit
    public function testDumpAndLoadWrappedWithClosingCommentSequence()
    {
        $inputArray = array('value' => 'contains */ closing comment', 'nested' => array('a' => '*/'));

        $string = Yaml::dumpWrapped($inputArray);
        $this->assertNotFalse($string);
        $this->assertIsString($string);

        $outputArray = Yaml::loadWrapped((string) $string);
        $this->assertIsArray($outputArray);
        $this->assertSame($inputArray, $outputArray);
    }

    public function testDumpWrappedUsesHaltCompilerGuard()
    {
        $inputArray = array('key' => 'value');
        $string = Yaml::dumpWrapped($inputArray);
        $this->assertIsString($string);
        $this->assertStringStartsWith('<?php __halt_compiler(); ?>', (string) $string);
    }

    public function testDumpAndLoadWrappedWithPhpTag()
    {
        $inputArray = array('code' => '<?php echo "hello"; ?>');

        $string = Yaml::dumpWrapped($inputArray);
        $this->assertNotFalse($string);
        $this->assertIsString($string);

        $outputArray = Yaml::loadWrapped((string) $string);
        $this->assertIsArray($outputArray);
        $this->assertSame($inputArray, $outputArray);
    }

    public function testLoadWrappedReadsOldCommentFormat()
    {
        $inputArray = array('one' => 1, 'two' => 'hello');
        $oldFormat = "<?php\n/*\n---\none: 1\ntwo: hello\n...\n*/\n";

        $outputArray = Yaml::loadWrapped($oldFormat);
        $this->assertIsArray($outputArray);
        $this->assertSame($inputArray, $outputArray);
    }

    public function testReadNoFile()
    {
        $this->assertFalse(Yaml::read('./no-such-file'));
    }

    public function testReadWrappedNoFile()
    {
        $this->assertFalse(Yaml::readWrapped('./no-such-file'));
    }

    public function testReadOversizedFile()
    {
        $tmpfname = tempnam(sys_get_temp_dir(), 'YAMLTEST');
        $this->assertNotFalse($tmpfname, 'Failed to create temp file');
        try {
            // Create a file just over 2MB
            $fh = fopen($tmpfname, 'w');
            $this->assertNotFalse($fh, 'Failed to open temp file for writing');
            $line = str_repeat('x', 1024) . "\n";
            for ($i = 0; $i < 2049; $i++) {
                fwrite($fh, $line);
            }
            fclose($fh);
            // Suppress the trigger_error warning
            $result = @Yaml::read($tmpfname);
            $this->assertFalse($result);
        } finally {
            if (file_exists($tmpfname)) {
                unlink($tmpfname);
            }
        }
    }

    public function testReadWrappedOversizedFile()
    {
        $tmpfname = tempnam(sys_get_temp_dir(), 'YAMLTEST');
        $this->assertNotFalse($tmpfname, 'Failed to create temp file');
        try {
            $fh = fopen($tmpfname, 'w');
            $this->assertNotFalse($fh, 'Failed to open temp file for writing');
            $line = str_repeat('x', 1024) . "\n";
            for ($i = 0; $i < 2049; $i++) {
                fwrite($fh, $line);
            }
            fclose($fh);
            $result = @Yaml::readWrapped($tmpfname);
            $this->assertFalse($result);
        } finally {
            if (file_exists($tmpfname)) {
                unlink($tmpfname);
            }
        }
    }
}
