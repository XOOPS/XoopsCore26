<?php
require_once(__DIR__.'/../../../../../init_new.php');

use Xoops\Core\Kernel\Handlers\XoopsBlock;
use Xoops\Core\Kernel\Handlers\XoopsBlockHandler;
use Xoops\Core\Kernel\Criteria;

class BlockHandlerTest extends \PHPUnit\Framework\TestCase
{
    use DatabaseTestTrait;

    protected $myClass='Xoops\Core\Kernel\Handlers\XoopsBlockHandler';
    protected $conn = null;
    protected $object;

    public function setUp(): void
    {
        $this->requireDatabase();
        $this->conn = Xoops::getInstance()->db();
        $this->conn->setSafe();
        $this->object = new XoopsBlockHandler($this->conn);
    }

    public function test___construct()
    {
        $this->assertMatchesRegularExpression('/^.*system_block$/', $this->object->table);
        $this->assertSame('\Xoops\Core\Kernel\Handlers\XoopsBlock', $this->object->className);
        $this->assertSame('bid', $this->object->keyName);
        $this->assertSame(null, $this->object->table_link);
        $this->assertSame('name', $this->object->identifierName);
        $this->assertSame(null, $this->object->field_link);
        $this->assertSame(null, $this->object->field_object);
    }

    public function testContracts()
    {
        $this->assertInstanceOf('\Xoops\Core\Kernel\Handlers\XoopsBlockHandler', $this->object);
        $this->assertInstanceOf('\Xoops\Core\Kernel\XoopsPersistableObjectHandler', $this->object);
    }

    public function test_insertBlock()
    {
        $block = new XoopsBlock();
        $block->setNew();
        $value = $this->object->insertBlock($block);
        $bid = $block->bid();
        $this->assertEquals($bid, $value);
        $value = $this->object->get($bid);
        $this->assertEquals($bid, $value->bid());
        $value = $this->object->deleteBlock($block);
        $this->assertSame(true, $value);
        $value = $this->object->get($bid);
        $this->assertSame(null, $value);
    }

    public function test_getDistinctObjects()
    {
        $value = $this->object->getDistinctObjects();
        $this->assertTrue(is_array($value));
    }

    public function test_getDistinctObjects100()
    {
        $criteria = new Criteria('bid');
        $value = $this->object->getDistinctObjects($criteria);
        $this->assertTrue(is_array($value));
    }

    public function test_getNameList()
    {
        $value = $this->object->getNameList();
        $this->assertTrue(is_array($value));
    }

    public function test_getAllBlocksByGroup()
    {
        $value = $this->object->getAllBlocksByGroup(1);
        $this->assertTrue(is_array($value));

        $value = $this->object->getAllBlocksByGroup(1, false);
        $this->assertTrue(is_array($value));

        $value = $this->object->getAllBlocksByGroup(array(1, 1, 1), false);
        $this->assertTrue(is_array($value));

        $value = $this->object->getAllBlocksByGroup(1, true, XOOPS_SIDEBLOCK_BOTH);
        $this->assertTrue(is_array($value));

        $value = $this->object->getAllBlocksByGroup(1, true, XOOPS_CENTERBLOCK_ALL);
        $this->assertTrue(is_array($value));

        $value = $this->object->getAllBlocksByGroup(1, true, -1);
        $this->assertTrue(is_array($value));

        $value = $this->object->getAllBlocksByGroup(1, true, null, 1);
        $this->assertTrue(is_array($value));
    }

    public function test_getAllBlocks()
    {
        $value = $this->object->getAllBlocks();
        $this->assertTrue(is_array($value));

        $value = $this->object->getAllBlocks('object', true, XOOPS_SIDEBLOCK_BOTH);
        $this->assertTrue(is_array($value));

        $value = $this->object->getAllBlocks('object', true, XOOPS_CENTERBLOCK_ALL);
        $this->assertTrue(is_array($value));

        $value = $this->object->getAllBlocks('object', true, -1);
        $this->assertTrue(is_array($value));

        $value = $this->object->getAllBlocks('object', true, null, 1);
        $this->assertTrue(is_array($value));

        $value = $this->object->getAllBlocks('list');
        $this->assertTrue(is_array($value));

        $value = $this->object->getAllBlocks('id');
        $this->assertTrue(is_array($value));
    }

    public function test_getByModule()
    {
        $value = $this->object->getByModule('module');
        $this->assertTrue(is_array($value));

        $value = $this->object->getByModule('module', false);
        $this->assertTrue(is_array($value));
    }

    public function test_getAllByGroupModule()
    {
        $value = $this->object->getAllByGroupModule(1);
        $this->assertTrue(is_array($value));

        $value = $this->object->getAllByGroupModule(array(1, 1, 1));
        $this->assertTrue(is_array($value));

        $value = $this->object->getAllByGroupModule(1, 1);
        $this->assertTrue(is_array($value));

        $value = $this->object->getAllByGroupModule(1, 1, true);
        $this->assertTrue(is_array($value));
    }

    public function test_getNonGroupedBlocks()
    {
        $value = $this->object->getNonGroupedBlocks();
        $this->assertTrue(is_array($value));

        $value = $this->object->getNonGroupedBlocks(1);
        $this->assertTrue(is_array($value));

        $value = $this->object->getNonGroupedBlocks(1, true);
        $this->assertTrue(is_array($value));

        $value = $this->object->getNonGroupedBlocks(0, false, true);
        $this->assertTrue(is_array($value));
    }

    public function test_countSimilarBlocks()
    {
        $value = $this->object->countSimilarBlocks(1, 1);
        $this->assertEquals(1, $value);

        $value = $this->object->countSimilarBlocks(1, 1, 'shows_func');
        $this->assertEquals(0, $value);
    }

    public function test_buildContent()
    {
        $value = $this->object->buildContent(0, 'titi', 'toto');
        $this->assertSame('tototiti', $value);

        $value = $this->object->buildContent(1, 'titi', 'toto');
        $this->assertSame('tititoto', $value);

        $value = $this->object->buildContent(2, 'titi', 'toto');
        $this->assertSame('', $value);
    }

    public function test_buildTitle()
    {
        $title = 'original';
        $value = $this->object->buildTitle($title);
        $this->assertEquals($title, $value);
        $title = 'original2';
        $new = 'new';
        $value = $this->object->buildTitle($title, $new);
        $this->assertEquals($new, $value);
    }

    public function test_getBlockByPerm()
    {
        $value = $this->object->getBlockByPerm(null);
        $this->assertTrue(empty($value) and is_array($value));

        $secondInstance = new XoopsBlockHandler($this->conn);
        $value = $secondInstance->getBlockByPerm(1);
        $this->assertTrue(is_array($value));
    }
}
