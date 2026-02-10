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
}
