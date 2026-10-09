<?php
require_once(__DIR__.'/../../init_new.php');

class XoopsDatabaseManagerTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = 'XoopsDatabaseManager';

    public function setUp(): void
    {
        global $xoopsDB;
        $xoopsDB = \XoopsDatabaseFactory::getDatabaseConnection(true);
    }

    public function test___construct()
    {
        $instance = new $this->myclass();
        $this->assertInstanceOf($this->myclass, $instance);
    }

    public function test___publicProperties()
    {
        $items = array('db', 'successStrings', 'failureStrings');
        foreach ($items as $item) {
            $prop = new ReflectionProperty($this->myclass, $item);
            $this->assertTrue($prop->isPublic());
        }
    }

    public function test_deleteTablesReturnsOnlyDroppedTables()
    {
        $manager = (new ReflectionClass($this->myclass))->newInstanceWithoutConstructor();
        // DROP TABLE succeeds (true, no rows affected) for one table and fails (null) for the other
        $manager->db = new class {
            public $dropped = array();

            public function prefix($table)
            {
                return 'x_' . $table;
            }

            public function query($sql)
            {
                $this->dropped[] = $sql;
                return (false !== strpos($sql, 'x_missing')) ? null : true;
            }
        };

        $this->assertSame(array('items'), $manager->deleteTables(array('items', 'missing')));
        $this->assertSame(array('DROP TABLE x_items', 'DROP TABLE x_missing'), $manager->db->dropped);
    }
}
