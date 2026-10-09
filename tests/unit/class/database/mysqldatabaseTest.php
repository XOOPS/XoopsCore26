<?php
require_once(__DIR__.'/../../init_new.php');

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Configuration;
use Doctrine\Common\EventManager;

class XoopsMySQLDatabaseTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = 'XoopsMySQLDatabase';

    public function test___construct()
    {
        $instance = new $this->myclass();
        $this->assertInstanceOf('\XoopsMySQLDatabase', $instance);
        $this->assertInstanceOf('\XoopsDatabase', $instance);
    }

    public function test___publicProperties()
    {
        $items = array('conn');
        foreach ($items as $item) {
            $prop = new ReflectionProperty($this->myclass, $item);
            $this->assertTrue($prop->isPublic());
        }
    }

    public function test_genId()
    {
        $instance = new $this->myclass();
        $sequence = 0;
        $x = $instance->genId($sequence);
        $this->assertSame(0, $x);
    }

    #[\PHPUnit\Framework\Attributes\RequiresPhpExtension('pdo_sqlite')]
    public function testGetAffectedRowsFollowsConnectionQuery()
    {
        $instance = new $this->myclass();
        $instance->conn = new \Xoops\Core\Database\Connection(
            array('driver' => 'pdo_sqlite', 'memory' => true),
            new \Doctrine\DBAL\Driver\PDO\SQLite\Driver(),
            new Configuration()
        );
        $instance->conn->setSafe(true);

        // DDL changes no rows: the query succeeds and reports 0 affected rows
        $this->assertTrue($instance->queryF('CREATE TABLE items (id INTEGER)'));
        $this->assertSame(0, $instance->getAffectedRows());

        $this->assertSame(2, $instance->queryF('INSERT INTO items VALUES (1), (2)'));
        $this->assertSame(2, $instance->getAffectedRows());

        $this->assertFalse($instance->queryF('INSERT INTO missing VALUES (1)'));
        $this->assertSame(0, $instance->getAffectedRows());
    }
}
