<?php
require_once __DIR__.'/../../init_new.php';

class Xoops_UtilsTest extends \PHPUnit\Framework\TestCase
{
    protected $myClass = '\Xoops\Utils';
    protected $save_SERVER = null;
    protected $save_ENV = null;

    public function setUp(): void
    {
        $this->save_SERVER = $_SERVER;
        $this->save_ENV = $_ENV;
        $this->markTestSkipped('side effects');
        if (!function_exists('ini_get') || ini_get('safe_mode') === '1') {
            $this->markTestSkipped('safe mode is on');
        }
    }

    public function tearDown(): void
    {
        $_SERVER = $this->save_SERVER;
        $_ENV = $this->save_ENV;
    }

    public function test_dumpVar()
    {
        $class = $this->myClass;
        $var = array(1 => 'test');
        ob_start();
        $x = $class::dumpVar($var, false, false);
        $buf = ob_get_clean();
        $this->assertTrue(is_string($x));
        $this->assertTrue(empty($buf));

        ob_start();
        $x = $class::dumpVar($var, true, false);
        $buf = ob_get_clean();
        $this->assertTrue(!empty($x));
        $this->assertTrue(is_string($x));
        $this->assertTrue(!empty($buf));
        $this->assertTrue(is_string($buf));
    }

    public function test_dumpFile()
    {
        $class = $this->myClass;
        $file = __FILE__;
        ob_start();
        $x = $class::dumpFile($file, false, false);
        $buf = ob_get_clean();
        $this->assertTrue(is_string($x));
        $this->assertTrue(empty($buf));

        ob_start();
        $x = $class::dumpFile($file, true, false);
        $buf = ob_get_clean();
        $this->assertTrue(!empty($x));
        $this->assertTrue(is_string($x));
        $this->assertTrue(!empty($buf));
        $this->assertTrue(is_string($buf));
    }

    public function test_arrayRecursiveDiff()
    {
        $class = $this->myClass;

        $array1 = array("a" => "green", "red", "blue", "red");
        $array2 = array("b" => "green", "yellow", "red");

        $x = $class::arrayRecursiveDiff($array1, $array1);
        $this->assertTrue(empty($x));
        $this->assertTrue(is_array($x));

        $x = $class::arrayRecursiveDiff($array1, $array2);
        $this->assertTrue(is_array($x));
        $this->assertTrue($x['a'] == 'green');
        $this->assertTrue($x[0] == 'red');
        $this->assertTrue($x[1] == 'blue');
        $this->assertTrue($x[2] == 'red');
    }

    public function test_arrayRecursiveDiff100()
    {
        $class = $this->myClass;
        $array1 = array("a" => "green", "red", array("a" => "green", "red", "blue"));
        $array2 = array("b" => "green", "red", array("b" => "green", "blue", "red"));

        $x = $class::arrayRecursiveDiff($array1, $array1);
        $this->assertTrue(empty($x));
        $this->assertTrue(is_array($x));

        $x = $class::arrayRecursiveDiff($array1, $array2);
        $this->assertTrue(is_array($x));
        $this->assertTrue($x['a'] == 'green');
        $this->assertTrue($x[1]['a'] == 'green');
        $this->assertTrue($x[1][0] == 'red');
        $this->assertTrue($x[1][1] == 'blue');
    }

    public function test_arrayRecursiveDiff120()
    {
        $class = $this->myClass;
        $array1 = array("a" => "green", "red", 'array' => array("a" => "green", "red", "blue"));
        $array2 = array("b" => "green", "red", 'array' => "blue");

        $x = $class::arrayRecursiveDiff($array1, $array1);
        $this->assertTrue(empty($x));
        $this->assertTrue(is_array($x));

        $x = $class::arrayRecursiveDiff($array1, $array2);
        $this->assertTrue(is_array($x));
        $this->assertTrue($x['a'] == 'green');
        $this->assertTrue($x['array']['a'] == 'green');
        $this->assertTrue($x['array'][0] == 'red');
        $this->assertTrue($x['array'][1] == 'blue');
    }

    public function test_arrayRecursiveDiff140()
    {
        $class = $this->myClass;
        $array1 = array("a" => "green", "red", 'array' => array("a" => "green", "red", "blue"));
        $array2 = array("b" => "green", "red", 'array' => array("b" => "green"));

        $x = $class::arrayRecursiveDiff($array1, $array1);
        $this->assertTrue(empty($x));
        $this->assertTrue(is_array($x));

        $x = $class::arrayRecursiveDiff($array1, $array2);
        $this->assertTrue(is_array($x));
        $this->assertTrue($x['a'] == 'green');
        $this->assertTrue($x['array']['a'] == 'green');
        $this->assertTrue($x['array'][0] == 'red');
        $this->assertTrue($x['array'][1] == 'blue');
    }

    public function test_arrayRecursiveDiff160()
    {
        $class = $this->myClass;
        $array1 = array("a" => "green", "red", 'array' => array("a" => "green", "red", "blue"));
        $array2 = array();

        $x = $class::arrayRecursiveDiff($array1, $array2);
        $this->assertTrue(is_array($x));
        $this->assertTrue($x == $array1);

        $x = $class::arrayRecursiveDiff($array2, $array1);
        $this->assertTrue(is_array($x));
        $this->assertTrue(empty($x));
    }

    public function test_arrayRecursiveMerge()
    {
        $class = $this->myClass;
        $array1 = array("a" => "green", "red", 'array' => array("a" => "green", "red", "blue"));
        $array2 = array("b" => "green", "red", 'array' => array("a" => "green", "yellow"));

        $x = $class::arrayRecursiveMerge($array1, $array2);
        $this->assertTrue(is_array($x));
        $this->assertTrue($x['a'] == 'green');
        $this->assertTrue($x['array']['a'] == 'green');
        $this->assertTrue($x['array'][0] == 'red');
        $this->assertTrue($x['array'][1] == 'blue');
        $this->assertTrue($x['b'] == 'green');
        $this->assertTrue($x['array'][2] == 'yellow');
    }

    public function test_arrayRecursiveMerge100()
    {
        $class = $this->myClass;
        $array1 = array("a" => "green", "red", 'array' => array("a" => "green", "red", "blue"));

        $x = $class::arrayRecursiveMerge($array1, $array1);
        $this->assertTrue(is_array($x));
        $this->assertTrue($x == $array1);
    }
}
