<?php
require_once(__DIR__.'/../../../../init_new.php');

class QueryBuilderTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = '\Xoops\Core\Database\QueryBuilder';
    protected $conn = null;

    public function setUp(): void
    {
        if (empty($this->conn)) {
            $this->conn = Xoops::getInstance()->db();
        }
    }

    public function test___construct()
    {
        $instance = new $this->myclass($this->conn);
        $this->assertInstanceOf('\Xoops\Core\Database\QueryBuilder', $instance);
        $this->assertInstanceOf('\Doctrine\DBAL\Query\QueryBuilder', $instance);
    }

    public function test_deletePrefix()
    {
        $this->markTestIncomplete('No test yet');
    }

    public function test_updatePrefix()
    {
        $this->markTestIncomplete('No test yet');
    }

    public function test_fromPrefix()
    {
        $this->markTestIncomplete('No test yet');
    }

    public function test_joinPrefix()
    {
        $this->markTestIncomplete('No test yet');
    }

    public function test_innerJoinPrefix()
    {
        $this->markTestIncomplete('No test yet');
    }

    public function test_leftJoinPrefix()
    {
        $this->markTestIncomplete('No test yet');
    }

    public function test_rightJoinPrefix()
    {
        $this->markTestIncomplete('No test yet');
    }
}
