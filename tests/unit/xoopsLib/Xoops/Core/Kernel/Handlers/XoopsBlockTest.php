<?php
require_once(__DIR__.'/../../../../../init_new.php');

use Xoops\Core\Kernel\Handlers\XoopsBlock;

class XoopsBlockTest extends \PHPUnit\Framework\TestCase
{
    use DatabaseTestTrait;

    protected $myClass = 'Xoops\Core\Kernel\Handlers\XoopsBlock';

    public function setUp(): void
    {
    }

    public function test___construct()
    {
        $instance = new $this->myClass();
        $this->assertInstanceOf($this->myClass, $instance);
        $value=$instance->getVars();
        $this->assertTrue(isset($value['bid']));
        $this->assertTrue(isset($value['mid']));
        $this->assertTrue(isset($value['func_num']));
        $this->assertTrue(isset($value['options']));
        $this->assertTrue(isset($value['name']));
        $this->assertTrue(isset($value['title']));
        $this->assertTrue(isset($value['content']));
        $this->assertTrue(isset($value['side']));
        $this->assertTrue(isset($value['weight']));
        $this->assertTrue(isset($value['visible']));
        $this->assertTrue(isset($value['block_type']));
        $this->assertTrue(isset($value['c_type']));
        $this->assertTrue(isset($value['isactive']));
        $this->assertTrue(isset($value['dirname']));
        $this->assertTrue(isset($value['func_file']));
        $this->assertTrue(isset($value['show_func']));
        $this->assertTrue(isset($value['edit_func']));
        $this->assertTrue(isset($value['template']));
        $this->assertTrue(isset($value['bcachetime']));
        $this->assertTrue(isset($value['last_modified']));
    }

    public function test___construct100()
    {
        $this->requireDatabase();
        $instance = new $this->myClass(1);
        $this->assertInstanceOf($this->myClass, $instance);
    }

    public function test_id()
    {
        $instance = new $this->myClass();
        $value = $instance->id();
        $this->assertSame(null, $value);
    }

    public function test_bid()
    {
        $instance = new $this->myClass();
        $value = $instance->bid();
        $this->assertSame(null, $value);
    }

    public function test_mid()
    {
        $instance = new $this->myClass();
        $value = $instance->mid();
        $this->assertSame(0, $value);
    }

    public function test_func_num()
    {
        $instance = new $this->myClass();
        $value = $instance->func_num();
        $this->assertSame(0, $value);
    }

    public function test_options()
    {
        $instance = new $this->myClass();
        $value = $instance->options();
        $this->assertSame(null, $value);
    }

    public function test_name()
    {
        $instance = new $this->myClass();
        $value = $instance->name();
        $this->assertSame(null, $value);
    }

    public function test_title()
    {
        $instance = new $this->myClass();
        $value = $instance->title();
        $this->assertSame(null, $value);
    }

    public function test_content()
    {
        $instance = new $this->myClass();
        $value = $instance->content();
        $this->assertSame(null, $value);
    }

    public function test_side()
    {
        $instance = new $this->myClass();
        $value = $instance->side();
        $this->assertSame(0, $value);
    }

    public function test_weight()
    {
        $instance = new $this->myClass();
        $value = $instance->weight();
        $this->assertSame(0, $value);
    }

    public function test_visible()
    {
        $instance = new $this->myClass();
        $value = $instance->visible();
        $this->assertSame(0, $value);
    }

    public function test_block_type()
    {
        $instance = new $this->myClass();
        $value = $instance->block_type();
        $this->assertSame(null, $value);
    }

    public function test_c_type()
    {
        $instance = new $this->myClass();
        $value = $instance->c_type();
        $this->assertSame(null, $value);
    }

    public function test_isactive()
    {
        $instance = new $this->myClass();
        $value = $instance->isactive();
        $this->assertSame(null, $value);
    }

    public function test_dirname()
    {
        $instance = new $this->myClass();
        $value = $instance->dirname();
        $this->assertSame(null, $value);
    }

    public function test_func_file()
    {
        $instance=new $this->myClass();
        $value = $instance->func_file();
        $this->assertSame(null, $value);
    }

    public function test_show_func()
    {
        $instance = new $this->myClass();
        $value = $instance->show_func();
        $this->assertSame(null, $value);
    }

    public function test_edit_func()
    {
        $instance = new $this->myClass();
        $value = $instance->edit_func();
        $this->assertSame(null, $value);
    }

    public function test_template()
    {
        $instance = new $this->myClass();
        $value = $instance->template();
        $this->assertSame($value, null);
    }

    public function test_bcachetime()
    {
        $instance = new $this->myClass();
        $value = $instance->bcachetime();
        $this->assertSame(0, $value);
    }

    public function test_last_modified()
    {
        $instance = new $this->myClass();
        $value = $instance->last_modified();
        $this->assertSame(0, $value);
    }

    public function test_getContent()
    {
        $this->markTestSkipped('side effects');
        $instance = new $this->myClass();
        $level = ob_get_level();
        $value = $instance->getContent();
        while (ob_get_level() > $level) {
            ob_end_clean();
        }
        $this->assertSame('', $value);
        $value = $instance->getContent('s', 'T');
        $this->assertSame('', $value);
        $value = $instance->getContent('s', 'H');
        $this->assertSame('', $value);
        $value = $instance->getContent('s', 'P');
        $this->assertSame('', $value);
        $value = $instance->getContent('s', 'S');
        $this->assertSame('', $value);
        $value = $instance->getContent('e');
        $this->assertSame('', $value);
        $value = $instance->getContent('default');
        $this->assertSame(null, $value);
    }

    public function test_getOptions()
    {
        $instance = new $this->myClass();
        $value = $instance->getOptions();
        $this->assertSame(false, $value);

        $xoops_root_path = \XoopsBaseConfig::get('root-path');
        require_once $xoops_root_path.'/modules/page/locale/en_US/en_US.php';
        require_once $xoops_root_path.'/modules/page/locale/en_US/locale.php';

        $instance->setVar('dirname', 'page');
        $instance->setVar('func_file', 'page_blocks.php');
        $instance->setVar('edit_func', 'page_blocks_edit');
        $instance->setVar('options', 'a|b|c|d|e');
        $value = $instance->getOptions();
        $this->assertTrue(is_string($value));

        $instance->setVar('dirname', 'page');
        $instance->setVar('func_file', 'page_blocks.php');
        $instance->setVar('edit_func', 'function_not_exists');
        $instance->setVar('options', 'a|b|c|d|e');
        $value = $instance->getOptions();
        $this->assertSame(false, $value);

        $instance->setVar('dirname', 'page');
        $instance->setVar('func_file', 'file_not_found.php');
        $instance->setVar('edit_func', 'page_blocks_edit');
        $instance->setVar('options', 'a|b|c|d|e');
        $value = $instance->getOptions();
        $this->assertSame(false, $value);
    }

    public function test_isCustom()
    {
        $instance = new $this->myClass();
        $value = $instance->isCustom();
        $this->assertFalse($value);

        $instance->setVar('block_type', XoopsBlock::BLOCK_TYPE_CUSTOM);
        $value = $instance->isCustom();
        $this->assertTrue($value);
    }

    public function test_buildBlock()
    {
        $this->markTestSkipped('side effects');
        $instance = new $this->myClass();
        $value = $instance->buildBlock();
        $this->assertSame(false, $value);

        $instance->setVar('block_type', '');
        $value = $instance->isCustom();
        $this->assertFalse($value);
        $value = $instance->buildBlock();
        $this->assertSame(false, $value);

        $instance->setVar('block_type', XoopsBlock::BLOCK_TYPE_CUSTOM);
        $value = $instance->isCustom();
        $this->assertTrue($value);

        $instance->setVar('dirname', 'page');
        $instance->setVar('func_file', 'page_blocks.php');
        $instance->setVar('show_func', 'page_blocks_show');
        $instance->setVar('options', 'a|b|c|d|e');
        $value = $instance->buildBlock();
        $this->assertSame(false, $value);

        $instance->setVar('dirname', 'page');
        $instance->setVar('func_file', 'page_blocks.php');
        $instance->setVar('show_func', 'function_not_exists');
        $instance->setVar('options', 'a|b|c|d|e');
        $value = $instance->buildBlock();
        $this->assertSame(false, $value);

        $instance->setVar('dirname', 'page');
        $instance->setVar('func_file', 'file_not_found.php');
        $instance->setVar('show_func', 'page_blocks_show');
        $instance->setVar('options', 'a|b|c|d|e');
        $value = $instance->buildBlock();
        $this->assertSame(false, $value);
    }
}
