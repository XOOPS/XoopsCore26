<?php
require_once(__DIR__.'/../../init_new.php');

class SqlUtilityTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = 'SqlUtility';

    public function setUp(): void
    {
    }

    public function test___construct()
    {
        $instance = new $this->myclass();
        $this->assertInstanceOf('\SqlUtility', $instance);
    }
    /*
    public function test_splitMySqlFile()
    {
        // splitMySqlFile
    }

    public function test_prefixQuery()
    {
        // prefixQuery
    }

    public function test_fromPrefix()
    {
        // fromPrefix
    }

    public function test_joinPrefix()
    {
        //  joinPrefix
    }

    public function test_innerJoinPrefix()
    {
        //  innerJoinPrefix
    }

    public function test_leftJoinPrefix()
    {
        //  leftJoinPrefix
    }

    public function test_rightJoinPrefix()
    {
        // rightJoinPrefix
    }
    */
}
