<?php
require_once(__DIR__.'/../../../init_new.php');

use Xoops\Core\Kernel\XoopsObject;
use Xoops\Core\ObjectTree;

class ObjectTreeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectTree
     */
    protected $object;

    private function getXoopsObjectDummy(): XoopsObject
    {
        return new class() extends XoopsObject{
            public function __construct()
            {
                $this->initVar('id', XOBJ_DTYPE_INT, 0);
                $this->initVar('pid', XOBJ_DTYPE_INT, 0);
                $this->initVar('rootid', XOBJ_DTYPE_INT, 0);
                parent::__construct();
            }
        };
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $objs = [];
        ($objs[] = $this->getXoopsObjectDummy())->setVars(['id'=>1, 'pid'=>0, 'rootid'=>1]);
        ($objs[] = $this->getXoopsObjectDummy())->setVars(['id'=>2, 'pid'=>1, 'rootid'=>1]);
        ($objs[] = $this->getXoopsObjectDummy())->setVars(['id'=>3, 'pid'=>0, 'rootid'=>3]);
        $this->object = new ObjectTree($objs, 'id', 'pid', 'rootid');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testContracts()
    {
        $instance = $this->object;
        $this->assertInstanceOf('\Xoops\Core\ObjectTree', $instance);
    }


    public function testGetByKey()
    {
        $obj=$this->object->getByKey(2);
        $this->assertInstanceOf('Xoops\Core\Kernel\XoopsObject', $obj);
        $this->assertEquals(2, $obj->getVar('id'));
        $this->assertEquals(1, $obj->getVar('pid'));
        $this->assertEquals(1, $obj->getVar('rootid'));
    }

    public function testGetAllParent()
    {
        $parents = $this->object->getAllParent(2);
        $this->assertCount(1, $parents);
    }
}
