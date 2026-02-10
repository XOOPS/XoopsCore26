<?php
require_once(__DIR__ . '/../../../../init_new.php');

use Xoops\Core\Kernel\Criteria;
use Xoops\Core\Kernel\Handlers\XoopsGroup;

class XoopsPersistableObjectHandlerTestInstance extends Xoops\Core\Kernel\XoopsPersistableObjectHandler
{
    public function __construct(
        \Xoops\Core\Database\Connection $db,
        $table = '',
        $className = '',
        $keyName = '',
        $identifierName = ''
    ) {
        parent::__construct($db, $table, $className, $keyName, $identifierName);
    }
}

class XoopsPersistableObjectHandlerTest extends \PHPUnit\Framework\TestCase
{
    use DatabaseTestTrait;

    protected $myClass = 'XoopsPersistableObjectHandlerTestInstance';
    protected $conn = null;

    public function setUp(): void
    {
        $this->requireDatabase();
        $this->conn = \Xoops\Core\Database\Factory::getConnection();
    }

    public function test___publicProperties()
    {
        $items = array('table', 'keyName', 'className', 'table_link', 'identifierName', 'field_link',
            'field_object');
        foreach ($items as $item) {
            $prop = new ReflectionProperty($this->myClass, $item);
            $this->assertTrue($prop->isPublic());
        }
    }

    public function test___construct()
    {
        $table = 'table';
        $className = 'className';
        $keyName = 'keyName';
        $identifierName = 'identifierName';
        $instance = new $this->myClass($this->conn, $table, $className, $keyName, $identifierName);
        $this->assertInstanceOf($this->myClass, $instance);
        $this->assertSame($this->conn, $instance->db2);
    }

    public function test_setHandler()
    {
        $instance = new $this->myClass($this->conn);
        $value = $instance->setHandler();
        $this->assertSame(null, $value);
    }

    public function test_loadHandler()
    {
        $instance = new $this->myClass($this->conn);
        $value = $instance->loadHandler('read');
        $this->assertTrue(is_object($value));
    }

    public function test_create()
    {
        $instance = new $this->myClass($this->conn);
        $value = $instance->create();
        $this->assertSame(false, $value);
    }

    public function test_get()
    {
        $instance = new $this->myClass($this->conn);
        $value = $instance->get();
        $this->assertSame(false, $value);
    }

    public function test_insert()
    {
        $instance = new $this->myClass($this->conn, 'system_group', 'Xoops\Core\Kernel\Handlers\XoopsGroup', 'groupid', 'name');
        $obj = new XoopsGroup();
        $obj->setDirty();
        $value = $instance->insert($obj);
        $this->assertSame('', $value);
    }

    public function test_delete()
    {
        $instance = new $this->myClass($this->conn, 'system_group', 'Xoops\Core\Kernel\Handlers\XoopsGroup', 'groupid', 'name');
        $obj = new XoopsGroup();
        $value = $instance->delete($obj);
        $this->assertSame(false, $value);
    }

    public function test_deleteAll()
    {
        $instance = new $this->myClass($this->conn, 'system_group', 'Xoops\Core\Kernel\Handlers\XoopsGroup', 'groupid', 'name');
        $criteria = new Criteria('dummy_field');
        $value = $instance->deleteAll($criteria);
        $this->assertSame(0, $value);
    }

    public function test_updateAll()
    {
        $instance = new $this->myClass($this->conn);
        $criteria = new Criteria('dummy_field');
        $value = $instance->updateAll('name', 'value', $criteria);
        $this->assertSame(0, $value);
    }

    public function test_getObjects()
    {
        $instance = new $this->myClass($this->conn, 'system_group', 'Xoops\Core\Kernel\Handlers\XoopsGroup', 'groupid', 'name');
        $value = $instance->getObjects();
        $this->assertTrue(is_array($value));
        $this->assertTrue($value > 0);
    }

    public function test_getAll()
    {
        $instance = new $this->myClass($this->conn, 'system_group', 'Xoops\Core\Kernel\Handlers\XoopsGroup', 'groupid', 'name');
        $value = $instance->getAll();
        $this->assertTrue(is_array($value));
        $this->assertTrue(count($value) > 0);
    }

    public function test_getList()
    {
        $instance = new $this->myClass($this->conn, 'system_group', 'Xoops\Core\Kernel\Handlers\XoopsGroup', 'groupid', 'name');
        $value = $instance->getList();
        $this->assertTrue(is_array($value));
        $this->assertTrue(count($value) > 0);
    }

    public function test_getIds()
    {
        $instance = new $this->myClass($this->conn, 'system_group', 'Xoops\Core\Kernel\Handlers\XoopsGroup', 'groupid', 'name');
        $value = $instance->getIds();
        $this->assertTrue(is_array($value));
        $this->assertTrue(count($value) > 0);
    }

    public function test_getCount()
    {
        $instance = new $this->myClass($this->conn, 'system_group', 'Xoops\Core\Kernel\Handlers\XoopsGroup', 'groupid', 'name');
        $value = $instance->getCount();
        $this->assertTrue((int)$value > 0);
    }

    public function test_getCounts()
    {
        $instance = new $this->myClass($this->conn, 'system_group', 'Xoops\Core\Kernel\Handlers\XoopsGroup', 'groupid', 'name');
        $value = $instance->getCounts();
        $this->assertTrue(is_array($value));
    }

    public function test_getByLink()
    {
        $instance = new $this->myClass($this->conn, 'system_group', 'Xoops\Core\Kernel\Handlers\XoopsGroup', 'groupid', 'name');
        $instance->field_object = 'groupid';
        $instance->table_link = $this->conn->prefix('system_permission');
        $instance->field_link = 'gperm_groupid';
        $value = $instance->getByLink();
        $this->assertTrue(is_array($value));
        $this->assertTrue(count($value) > 0);
    }

    public function test_getCountByLink()
    {
        $instance = new $this->myClass($this->conn, 'system_group', 'Xoops\Core\Kernel\Handlers\XoopsGroup', 'groupid', 'name');
        $instance->field_object = 'groupid';
        $instance->table_link = $this->conn->prefix('system_permission');
        $instance->field_link = 'gperm_groupid';
        $value = $instance->getCountByLink();
        $this->assertTrue((int)$value > 0);
    }

    public function test_getCountsByLink()
    {
        $instance = new $this->myClass($this->conn, 'system_group', 'Xoops\Core\Kernel\Handlers\XoopsGroup', 'groupid', 'name');
        $instance->field_object = 'groupid';
        $instance->table_link = $this->conn->prefix('system_permission');
        $instance->field_link = 'gperm_groupid';
        $value = $instance->getCountsByLink();
        $this->assertTrue(is_array($value));
    }

    public function test_updateByLink()
    {
        $instance = new $this->myClass($this->conn, 'system_group', 'Xoops\Core\Kernel\Handlers\XoopsGroup', 'groupid', 'name');
        $instance->field_object = 'groupid';
        $instance->table_link = $this->conn->prefix('system_permission');
        $instance->field_link = 'gperm_groupid';
        $value = $instance->updateByLink(array('key' => 'value'));
        $this->assertSame(false, $value);
    }

    public function test_deleteByLink()
    {
        $instance = new $this->myClass($this->conn, 'system_group', 'Xoops\Core\Kernel\Handlers\XoopsGroup', 'groupid', 'name');
        $instance->field_object = 'groupid';
        $instance->table_link = $this->conn->prefix('system_permission');
        $instance->field_link = 'gperm_groupid';
        $value = $instance->deleteByLink();
        $this->assertSame(false, $value);
    }

    public function test_cleanOrphan()
    {
        $this->markTestSkipped('side effects');
        $instance = new $this->myClass($this->conn, 'system_group', 'Xoops\Core\Kernel\Handlers\XoopsGroup', 'groupid', 'name');
        $value = $instance->cleanOrphan($this->conn->prefix('system_permission'), 'gperm_groupid', 'groupid');
        $this->assertSame(0, $value);
    }

    public function test_synchronization()
    {
        $this->markTestSkipped('side effects');
        $instance = new $this->myClass($this->conn, 'system_group', 'Xoops\Core\Kernel\Handlers\XoopsGroup', 'groupid', 'name');
        $value = $instance->synchronization($this->conn->prefix('system_permission'), 'gperm_groupid', 'groupid');
        $this->assertSame(0, $value);
    }
}
