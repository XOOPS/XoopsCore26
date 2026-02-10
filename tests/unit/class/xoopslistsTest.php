<?php
require_once(__DIR__.'/../init_new.php');

class xoopslistsTest extends \PHPUnit\Framework\TestCase
{
    protected $myClass = 'XoopsLists';
    protected $conn = null;

    public function setUp(): void
    {
        if (empty($this->conn)) {
            $this->conn = Xoops::getInstance()->db();
        }
    }

    public function test_getTimeZoneList()
    {
        $class = $this->myClass;
        $value = $class::getTimeZoneList();
        foreach ($value as $k => $v) {
            $this->assertTrue(is_string($k));
            $this->assertTrue(is_string($v));
        }
    }

    public function test_getThemesList()
    {
        $class = $this->myClass;
        $theme_path = \XoopsBaseConfig::get('themes-path');
        $list_ref = $class::getDirListAsArray($theme_path . '/');
        $value = $class::getThemesList();
        $this->assertSame($list_ref, $value);

        $list_ref = $class::getDirListAsArray($theme_path);
        $value = $class::getThemesList();
        $this->assertSame($list_ref, $value);
    }

    public function test_getModulesList()
    {
        $class = $this->myClass;
        $xoops_root_path = \XoopsBaseConfig::get('root-path');
        $list_ref = $class::getDirListAsArray($xoops_root_path . '/modules/');
        $value = $class::getModulesList();
        $this->assertSame($list_ref, $value);
    }

    public function test_getEditorList()
    {
        $class = $this->myClass;
        $xoops_root_path = \XoopsBaseConfig::get('root-path');
        $list_ref = $class::getDirListAsArray($xoops_root_path . '/class/xoopseditor/');
        $value = $class::getEditorList();
        $this->assertSame($list_ref, $value);
    }

    public function test_getFileListAsArray()
    {
        $class = $this->myClass;
        $xoops_root_path = \XoopsBaseConfig::get('root-path');
        $value = $class::getFileListAsArray($xoops_root_path . '/class/xoopseditor/');
        $this->assertTrue(is_array($value));
        $this->assertTrue(count($value)>0);
        $prefix='toto';
        $value = $class::getFileListAsArray($xoops_root_path . '/class/xoopseditor/', $prefix);
        $this->assertSame(0, strncmp(array_shift($value), $prefix, strlen($prefix)));
    }

    public function test_getImgListAsArray()
    {
        $class = $this->myClass;
        $xoops_root_path = \XoopsBaseConfig::get('root-path');
        $value = $class::getImgListAsArray($xoops_root_path . '/images/');
        $this->assertTrue(is_array($value));
        $this->assertTrue(count($value)>0);
        $prefix='toto';
        $value = $class::getImgListAsArray($xoops_root_path . '/images/', $prefix);
        $this->assertSame(0, strncmp(array_shift($value), $prefix, strlen($prefix)));
    }

    public function test_getHtmlListAsArray()
    {
        $class = $this->myClass;
        $xoops_root_path = \XoopsBaseConfig::get('root-path');
        $value = $class::getHtmlListAsArray($xoops_root_path . '/themes/');
        $this->assertTrue(is_array($value));
        $this->assertTrue(count($value)>0);
        $prefix='toto';
        $value = $class::getHtmlListAsArray($xoops_root_path . '/themes/', $prefix);
        $this->assertSame(0, strncmp(array_shift($value), $prefix, strlen($prefix)));
    }

    public function test_getSubjectsList()
    {
        $class = $this->myClass;
        $xoops_root_path = \XoopsBaseConfig::get('root-path');
        $d_subject = $xoops_root_path . '/images/subject/';
        $is_dir = is_dir($d_subject);
        if (! $is_dir) {
            mkdir($d_subject);
        }
        $value = $class::getSubjectsList();
        $this->assertTrue(is_array($value));
        $this->assertTrue(count($value)>0);
        $sdir = 'test_getSubjectsList';
        $is_sdir = is_dir($d_subject.$sdir);
        if (! $is_dir) {
            mkdir($d_subject.$sdir);
        }
        $value = $class::getSubjectsList($sdir);
        $this->assertTrue(is_array($value));

        @rmdir($d_subject.$sdir);
        @rmdir($d_subject);
    }

    public function test_getLangList()
    {
        $class = $this->myClass;
        $value = $class::getLangList();
        $this->assertTrue(is_array($value));
        $this->assertTrue(count($value)>0);
    }

    public function test_getLocaleList()
    {
        $class = $this->myClass;
        $value = $class::getLocaleList();
        $this->assertTrue(is_array($value));
        $this->assertTrue(count($value)>0);
    }

    public function test_340()
    {
        $class = $this->myClass;
        $value = $class::getCountryList();
        $this->assertTrue(is_array($value));
        foreach ($value as $k => $v) {
            if (empty($k)) {
                $this->assertSame('-', $v);
            } else {
                $this->assertMatchesRegularExpression('/^[A-Z][A-Z]$/', $k);
                $this->assertTrue(is_string($v));
            }
        }
    }
}
