<?php
require_once(__DIR__.'/../../init_new.php');

class XoopsFileHandlerTest extends \PHPUnit\Framework\TestCase
{
    protected $myClass = 'XoopsFileHandler';

    public function test___construct()
    {
        $instance = new $this->myClass(__FILE__);
        $this->assertInstanceOf($this->myClass, $instance);
    }

    public function test_publicProperties()
    {
        $items = array('folder', 'name', 'info', 'handle', 'lock');
        foreach ($items as $item) {
            $prop = new ReflectionProperty($this->myClass, $item);
            $this->assertTrue($prop->isPublic());
        }
    }

    public function test_create()
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'dummy.txt';
        @unlink($file);
        $instance = new $this->myClass($file, true);
        $this->assertTrue(file_exists($file));
        $this->assertSame(basename($file), $instance->name);
        $this->assertSame(dirname($file), $instance->folder->path);
        $this->assertTrue(@unlink($file));
    }

    public function test_open()
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'dummy.txt';
        @unlink($file);
        $instance = new $this->myClass($file, false);
        $this->assertFalse(file_exists($file));

        $result = $instance->open();
        $this->assertTrue($result);
        $this->assertTrue(file_exists($file));

        $result = $instance->open('r', true);
        $this->assertTrue($result);

        $result = $instance->open('r', true); // test force to reopen
        $this->assertTrue($result);

        // this assertion was specific to windows, on linux it will unlink will succeed even
        // if the file is open, as the actual deletion happens when all references are dropped.
        //$this->assertFalse(@unlink($file)); // fail to delete opened file

        unset($instance); // test destructor
        $this->assertTrue(@unlink($file)); // ok to delete closed file

        $instance = new $this->myClass($file, false);
        $this->assertFalse(file_exists($file));

        $result = $instance->open('z');
        $this->assertFalse($result);

        unset($instance);
        $this->assertTrue(@unlink($file));
    }

    public function test_read()
    {
        $file = __FILE__;
        $instance = new $this->myClass($file, false);

        $result = $instance->read();
        $result2 = file_get_contents($file);
        $this->assertSame($result, $result2);

        unset($instance);
        $instance = new $this->myClass($file, false);
        $bytes = 128;
        $result = $instance->read($bytes);
        $str = file_get_contents($file);
        $result2 = substr($str, 0, $bytes);
        $this->assertSame($result, $result2);

        unset($instance);
        $instance = new $this->myClass($file, false);
        $bytes = 'notInt';
        $result1 = $instance->read($bytes);
        $value = $instance->offset(0);
        $this->assertTrue($value);
        $data = '';
        while (!feof($instance->handle)) {
            $data .= fgets($instance->handle, 4096);
        }
        $data = trim($data);
        $this->assertSame($result1, $data);
    }

    public function test_offset()
    {
        // see also test_read for test

        $file = __FILE__;
        $instance = new $this->myClass($file, false);
        $bytes = 128;
        $result = $instance->read($bytes);
        $value = $instance->offset(false);
        $this->assertTrue(is_int($value));
        $this->assertTrue($value > 0);

        $this->markTestSkipped('platform issues?');
        // appears this is testing possibly undefined behavior? Final assertion fails on linux
        unset($instance);
        $instance = new $this->myClass($file, false);
        $instance->handle = $instance->name = null; // force open to return false
        $result = $instance->offset(0);
        $this->assertFalse($result);
    }

    public function test_prepare()
    {
        $file = __FILE__;
        $instance = new $this->myClass($file, false);

        $data = "line1\nline2\r\nline3\r";
        $value = $instance->prepare($data);
        if (substr(PHP_OS, 0, 3) == 'WIN') {
            $target = "line1\r\nline2\r\nline3\r\n";
        } else {
            $target = "line1\nline2\nline3\n";
        }
        $this->assertSame($target, $value);
    }

    public function test_write()
    {
        $this->markTestIncomplete();
    }

    public function test_append()
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'dummy.txt';
        @unlink($file);
        $instance = new $this->myClass($file, false);
        $this->assertFalse($instance->exists($file));
        $data = "dummy for unit tests";
        $value = $instance->write($data);
        $this->assertTrue($value);

        $data = "dummy for unit tests";
        $value = $instance->append($data);
        $this->assertTrue($value);
        @unlink($file);
    }

    public function test_close()
    {
        // see other test when unset $instance

        $file = __FILE__;
        $instance = new $this->myClass($file, false);
        $instance->handle = $instance->name = null; // force open to return false
        $result = $instance->close();
        $this->assertTrue($result);
    }

    public function test_delete()
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'dummy.txt';
        @unlink($file);
        $instance = new $this->myClass($file, false);
        $this->assertFalse($instance->exists($file));
        $value = $instance->delete();
        $this->assertFalse($value);

        unset($instance);
        $instance = new $this->myClass($file, true);
        $this->assertTrue($instance->exists($file));
        $value = $instance->delete();
        $this->assertTrue($value);
        $this->assertFalse($instance->exists($file));
    }

    public function test_info()
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'dummy.txt';
        @unlink($file);
        $instance = new $this->myClass($file, true);
        $this->assertTrue($instance->exists($file));
        $value = $instance->info();
        $this->assertTrue(is_array($value));
        $this->assertSame(dirname($file), $value['dirname']);
        $this->assertSame(basename($file), $value['basename']);
        $this->assertSame('txt', $value['extension']);
        $this->assertSame(basename($file, '.txt'), $value['filename']);
        $instance->delete();
    }

    public function test_ext()
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'dummy.txt';
        @unlink($file);
        $instance = new $this->myClass($file, true);
        $this->assertTrue($instance->exists($file));
        $value = $instance->ext();
        $this->assertSame('txt', $value);
        $instance->delete();
    }

    public function test_name()
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'dummy.txt';
        @unlink($file);
        $instance = new $this->myClass($file, true);
        $this->assertTrue($instance->exists($file));
        $value = $instance->name();
        $this->assertSame(basename($file, '.txt'), $value);
        $instance->delete();

        $file = __DIR__ . DIRECTORY_SEPARATOR . 'dummy';
        @unlink($file);
        $instance = new $this->myClass($file, true);
        $this->assertTrue($instance->exists($file));
        $this->assertFalse($instance->ext()); // ensure no extension
        $value = $instance->name();
        $this->assertSame(basename($file), $value);
        $instance->delete();
    }

    public function test_safe()
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'dummy#(001).txt';
        @unlink($file);
        $instance = new $this->myClass($file, false);

        $value = $instance->safe();
        $this->assertSame('dummy_001_.', $value);

        $value = $instance->safe(null, 'txt');
        $this->assertSame('dummy_001_.', $value);

        $value = $instance->safe(basename($file), 'txt');
        $this->assertSame('dummy_001_.', $value);
    }

    public function test_md5()
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'dummy.txt';
        @unlink($file);
        $instance = new $this->myClass($file, true);
        $this->assertTrue($instance->exists($file));

        $data = "dummy for unit tests";
        $value = $instance->write($data);
        $this->assertTrue($value);

        $result = $instance->close();
        $this->assertTrue($result);

        $value = $instance->md5(true);
        $this->assertSame(md5_file($instance->pwd()), $value);

        $value = $instance->md5();
        $this->assertSame(md5_file($instance->pwd()), $value);

        $value = $instance->md5(0);
        $this->assertFalse($value);
    }

    public function test_pwd()
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'dummy.txt';
        @unlink($file);
        $instance = new $this->myClass($file, true);
        $this->assertTrue($instance->exists($file));
        $value = $instance->pwd();
        $this->assertSame($file, $value);
        $instance->delete();
    }

    public function test_perms()
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'dummy.txt';
        @unlink($file);
        $str = "a string for test";
        $result = file_put_contents($file, $str);
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        $this->assertTrue(is_int($result));
        $instance = new $this->myClass($file, false);
        $this->assertTrue($instance->exists($file));
        $value = $instance->perms();
        $this->assertSame($perms, $value);
        @unlink($file);

        unset($instance);
        $instance = new $this->myClass($file, false);
        $this->assertFalse($instance->exists($file));
        $value = $instance->perms();
        $this->assertFalse($value);
    }

    public function test_size()
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'dummy.txt';
        @unlink($file);
        $str = "a string for test";
        $length = strlen($str);
        $result = file_put_contents($file, $str);
        $this->assertTrue(is_int($result));
        $instance = new $this->myClass($file, false);
        $this->assertTrue($instance->exists($file));
        $value = $instance->size();
        $this->assertSame($length, $value);
        @unlink($file);

        unset($instance);
        $instance = new $this->myClass($file, false);
        $this->assertFalse($instance->exists($file));
        $value = $instance->size();
        $this->assertFalse($value);
    }

    public function test_writable()
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'dummy.txt';
        @unlink($file);
        $str = "a string for test";
        $result = file_put_contents($file, $str);
        $this->assertTrue(is_int($result));
        $instance = new $this->myClass($file, false);
        $this->assertTrue($instance->exists($file));
        $value = $instance->writable();
        $this->assertTrue($value);
        @unlink($file);

        unset($instance);
        $instance = new $this->myClass($file, false);
        $this->assertFalse($instance->exists($file));
        $value = $instance->writable();
        $this->assertFalse($value);
    }

    public function test_executable()
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'dummy.txt';
        @unlink($file);
        $str = "a string for test";
        $result = file_put_contents($file, $str);
        $this->assertTrue(is_int($result));
        $instance = new $this->myClass($file, false);
        $this->assertTrue($instance->exists($file));
        $value = $instance->executable();
        $this->assertFalse($value);
        @unlink($file);

        unset($instance);
        $instance = new $this->myClass($file, false);
        $this->assertFalse($instance->exists($file));
        $value = $instance->executable();
        $this->assertFalse($value);
    }

    public function test_readable()
    {
        $file = __DIR__. DIRECTORY_SEPARATOR .'dummy.txt';
        @unlink($file);
        $str = "a string for test";
        $result = file_put_contents($file, $str);
        $this->assertTrue(is_int($result));
        $instance = new $this->myClass($file, false);
        $this->assertTrue($instance->exists($file));
        $value = $instance->readable();
        $this->assertTrue($value);
        @unlink($file);

        unset($instance);
        $instance = new $this->myClass($file, false);
        $this->assertFalse($instance->exists($file));
        $value = $instance->readable();
        $this->assertFalse($value);
    }

    public function test_owner()
    {
        $file = __DIR__. DIRECTORY_SEPARATOR .'dummy.txt';
        @unlink($file);
        $str = "a string for test";
        $result = file_put_contents($file, $str);
        $this->assertTrue(is_int($result));
        $instance = new $this->myClass($file, false);
        $this->assertTrue($instance->exists($file));
        $value = $instance->owner();
        $this->assertTrue(is_int($value));
        @unlink($file);

        unset($instance);
        $instance = new $this->myClass($file, false);
        $this->assertFalse($instance->exists($file));
        $value = $instance->owner();
        $this->assertFalse($value);
    }

    public function test_group()
    {
        $file = __DIR__. DIRECTORY_SEPARATOR .'dummy.txt';
        @unlink($file);
        $str = "a string for test";
        $result = file_put_contents($file, $str);
        $this->assertTrue(is_int($result));
        $instance = new $this->myClass($file, false);
        $this->assertTrue($instance->exists($file));
        $value = $instance->group();
        $this->assertTrue(is_int($value));
        @unlink($file);

        unset($instance);
        $instance = new $this->myClass($file, false);
        $this->assertFalse($instance->exists($file));
        $value = $instance->group();
        $this->assertFalse($value);
    }

    public function test_lastAccess()
    {
        $file = __DIR__. DIRECTORY_SEPARATOR .'dummy.txt';
        @unlink($file);
        $str = "a string for test";
        $result = file_put_contents($file, $str);
        $this->assertTrue(is_int($result));
        $atime = fileatime($file);
        $this->assertTrue(is_int($atime));
        $instance = new $this->myClass($file, false);
        $this->assertTrue($instance->exists($file));
        $value = $instance->lastAccess();
        $this->assertSame($atime, $value);
        @unlink($file);

        unset($instance);
        $instance = new $this->myClass($file, false);
        $this->assertFalse($instance->exists($file));
        $value = $instance->lastAccess();
        $this->assertFalse($value);
    }

    public function test_lastChange()
    {
        $file = __DIR__. DIRECTORY_SEPARATOR .'dummy.txt';
        @unlink($file);
        $str = "a string for test";
        $result = file_put_contents($file, $str);
        $this->assertTrue(is_int($result));
        $atime = filemtime($file);
        $this->assertTrue(is_int($atime));
        $instance = new $this->myClass($file, false);
        $this->assertTrue($instance->exists($file));
        $value = $instance->lastChange();
        $this->assertSame($atime, $value);
        @unlink($file);

        unset($instance);
        $instance = new $this->myClass($file, false);
        $this->assertFalse($instance->exists($file));
        $value = $instance->lastChange();
        $this->assertFalse($value);
    }

    public function test_folder()
    {
        $file = __DIR__. DIRECTORY_SEPARATOR .'dummy.txt';
        @unlink($file);
        $instance = new $this->myClass($file, true);
        $this->assertTrue($instance->exists($file));
        $folder = $instance->folder();
        $this->assertInstanceOf('XoopsFolderHandler', $folder);
        $this->assertSame(dirname($file), $folder->path);
        @unlink($file);
    }
}
