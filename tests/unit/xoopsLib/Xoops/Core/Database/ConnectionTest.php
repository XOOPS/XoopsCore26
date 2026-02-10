<?php
require_once(__DIR__.'/../../../../init_new.php');


use Doctrine\DBAL\Configuration;

class ConnectionTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = '\Xoops\Core\Database\Connection';

    /** @var  Xoops\Core\Database\Connection */
    protected $object;

    public function setUp(): void
    {
        $params = array('driver' => 'pdo_mysql');
        $config = new Configuration();
        $driver = new Doctrine\DBAL\Driver\PDO\MySQL\Driver();

        $this->object = new $this->myclass($params, $driver, $config);
    }

    public function test___construct()
    {
        $params = array('driver' => 'pdo_mysql');
        $config = new Configuration();
        $driver = new Doctrine\DBAL\Driver\PDO\MySQL\Driver();

        $instance = new $this->myclass($params, $driver, $config);
        $this->assertInstanceOf('\Xoops\Core\Database\Connection', $instance);
    }

    public function test_setSafe()
    {
        $class = $this->myclass;
        $this->object->setSafe(true);
        $x = $this->object->getSafe();
        $this->assertTrue($x);

        $this->object->setSafe(false);
        $x = $this->object->getSafe();
        $this->assertFalse($x);
    }

    public function test_setForce()
    {
        $this->object->setForce(true);
        $x = $this->object->getForce();
        $this->assertTrue($x);

        $this->object->setForce(false);
        $x = $this->object->getForce();
        $this->assertFalse($x);
    }

    public function test_prefix()
    {
        $x = $this->object->prefix('');
        $db_prefix = \XoopsBaseConfig::get('db-prefix');
        $this->assertSame($db_prefix, $x);

        $table = 'toto';
        $x = $this->object->prefix($table);
        $this->assertSame($db_prefix.'_'.$table, $x);
    }

    public function test_insertPrefix()
    {
        $this->markTestIncomplete('No test yet');
    }

    public function test_updatePrefix()
    {
        $this->markTestIncomplete('No test yet');
    }

    public function test_deletePrefix()
    {
        $this->markTestIncomplete('No test yet');
    }

    public function test_executeQuery()
    {
        $this->markTestIncomplete('No test yet');
    }

    public function test_executeStatement()
    {
        $this->markTestIncomplete('No test yet');
    }

    public function test_beginTransaction()
    {
        $this->markTestIncomplete('No test yet');
    }

    public function test_commit()
    {
        $this->markTestIncomplete('No test yet');
    }

    public function test_rollBack()
    {
        $this->markTestIncomplete('No test yet');
    }

    public function test_query()
    {
        $this->markTestIncomplete('No test yet');
    }

    public function test_queryFromFile()
    {
        $this->markTestIncomplete('No test yet');
    }

    public function test_createXoopsQueryBuilder()
    {
        $this->markTestIncomplete('No test yet');
    }
}
