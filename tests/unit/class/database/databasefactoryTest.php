<?php
require_once(__DIR__.'/../../init_new.php');

class XoopsDatabaseFactoryTest extends \PHPUnit\Framework\TestCase
{
    protected $myClass = 'XoopsDatabaseFactory';

    public function setUp(): void
    {
    }

    public function test_getDatabaseConnection()
    {
        $class = $this->myClass;
        $instance = $class::getDatabaseConnection();
        $this->assertStringStartsWith('XoopsMySQLDatabase', get_class($instance));

        $instance2 = $class::getDatabaseConnection();
        $this->assertSame($instance, $instance2);
        $this->assertInstanceOf('\\Xoops\\Core\\Database\\Connection', $instance->conn);
        $driver = $instance->conn->getDriver();
        //$driver_conn = $driver->connect(array()); // not always possible
        $this->assertInstanceOf('\\Doctrine\\DBAL\\Driver', $driver);
        $this->assertSame(\XoopsBaseConfig::get('db-prefix').'_test', $instance->prefix('test'));
        $this->assertSame(\XoopsBaseConfig::get('db-prefix'), $instance->prefix());
    }
}
