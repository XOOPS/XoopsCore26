<?php
require_once(__DIR__.'/../../../../../init_new.php');

use Xoops\Core\Kernel\Handlers\XoopsTplFileHandler;
use Xoops\Core\Kernel\Handlers\XoopsTplFile;
use Xoops\Core\Kernel\Criteria;

class XoopsTplFileHandlerTest extends \PHPUnit\Framework\TestCase
{
    use DatabaseTestTrait;

    protected $myclass='Xoops\Core\Kernel\Handlers\XoopsTplFileHandler';
    protected $conn = null;
    protected $xoopsTplfile = '\Xoops\Core\Kernel\Handlers\XoopsTplFile';

    public function setUp(): void
    {
        $this->requireDatabase();
        $this->conn = Xoops::getInstance()->db();
        $this->conn->setSafe();
    }

    public function test___construct()
    {
        $instance = new $this->myclass($this->conn);
        $this->assertMatchesRegularExpression('/^.*system_tplfile$/', $instance->table);
        $this->assertSame($this->xoopsTplfile, $instance->className);
        $this->assertSame('tpl_id', $instance->keyName);
        $this->assertSame('tpl_refid', $instance->identifierName);
    }

    public function testContracts()
    {
        $instance=new $this->myclass($this->conn);
        $this->assertInstanceOf('\Xoops\Core\Kernel\Handlers\XoopsTplFileHandler', $instance);
        $this->assertInstanceOf('\Xoops\Core\Kernel\XoopsPersistableObjectHandler', $instance);
    }

    public function test_getById()
    {
        $instance = new $this->myclass($this->conn);
        $id = 1;
        $value = $instance->getById($id);
        $this->assertInstanceOf($this->xoopsTplfile, $value);

        $value = $instance->getById($id, true);
        $this->assertInstanceOf($this->xoopsTplfile, $value);
    }

    public function test_loadSource()
    {
        $instance = new $this->myclass($this->conn);
        $source = new XoopsTplFile();
        $value = $instance->loadSource($source);
        $this->assertSame(true, $value);

        $source->setVar('tpl_id', 1);
        $value = $instance->loadSource($source);
        $this->assertSame(true, $value);
        $tmp = $source->tpl_source();
        $this->assertTrue(!empty($tmp));
    }

    public function test_insertTpl()
    {
        $instance = new $this->myclass($this->conn);
        $source = new XoopsTplFile();
        $value = $instance->insertTpl($source);
        $this->assertSame(true, $value);
    }

    public function test_forceUpdate()
    {
        $instance = new $this->myclass($this->conn);
        $source = new XoopsTplFile();
        $value = $instance->forceUpdate($source);
        $this->assertSame(true, $value);
    }

    public function test_deleteTpl()
    {
        $instance = new $this->myclass($this->conn);
        $source = new XoopsTplFile();
        $source->setDirty();
        $source->setNew();
        $source->setVar('tpl_refid', 1);
        $source->setVar('tpl_lastmodified', 1);
        $source->setVar('tpl_lastimported', 1);
        $source->setVar('tpl_module', 'TPL_DESC_DUMMY_TEST');
        $source->setVar('tpl_tplset', 'TPL_DESC_DUMMY_TEST');
        $source->setVar('tpl_file', 'TPL_DESC_DUMMY_TEST');
        $source->setVar('tpl_desc', 'TPL_DESC_DUMMY_TEST');
        $source->setVar('tpl_type', 'TPL_DESC_DUMMY_TEST');
        $value = $instance->insertTpl($source);
        $this->assertSame(true, $value);
        $value = $instance->deleteTpl($source);
        $this->assertSame(true, $value);
    }

    public function test_getTplObjects()
    {
        $instance = new $this->myclass($this->conn);
        $value = $instance->getTplObjects();
        $this->assertTrue(is_array($value));

        $value = $instance->getTplObjects(null, true);
        $this->assertTrue(is_array($value));

        $value = $instance->getTplObjects(null, false, true);
        $this->assertTrue(is_array($value));

        $criteria = new Criteria('tpl_type', 'block');
        $value = $instance->getTplObjects($criteria);
        $this->assertTrue(is_array($value));
        $this->assertTrue(count($value) > 0);
    }

    public function test_getModuleTplCount()
    {
        $instance = new $this->myclass($this->conn);
        $value = $instance->getModuleTplCount('toto');
        $this->assertTrue(empty($value));

        $value = $instance->getModuleTplCount('default');
        $this->assertTrue(is_array($value));
        $this->assertTrue(count($value) > 0);
    }

    public function test_find()
    {
        $instance = new $this->myclass($this->conn);
        $value = $instance->find();
        $this->assertTrue(is_array($value));

        $value = $instance->find('tpl_set');
        $this->assertTrue(is_array($value));

        $value = $instance->find(null, null, null, 'module');
        $this->assertTrue(is_array($value));

        $value = $instance->find(null, null, 1);
        $this->assertTrue(is_array($value));

        $value = $instance->find(null, null, null, null, 'file');
        $this->assertTrue(is_array($value));

        $value = $instance->find(null, 1);
        $this->assertTrue(is_array($value));

        $value = $instance->find(null, array(1, 2, 3));
    }

    public function test_templateExists()
    {
        $instance = new $this->myclass($this->conn);

        $value = $instance->templateExists('dummy.tpl', 'dummy');
        $this->assertSame(false, $value);

        $value = $instance->templateExists('system_block_user.tpl', 'default');
        $this->assertSame(true, $value);
    }
}
