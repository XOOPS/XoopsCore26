<?php
require_once(__DIR__.'/../../../init_new.php');

class MediaUploaderTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = '\Xoops\Core\MediaUploader';

    public function setUp(): void
    {
    }

    public function test___construct()
    {
        $upload_dir = 'upload_dir';
        $allowed_mime_types = array('toto');
        $theme = new  $this->myclass($upload_dir, $allowed_mime_types);
        $this->assertInstanceOf($this->myclass, $theme);
        $this->assertSame(false, $theme->allowUnknownTypes);
        $this->assertTrue(property_exists($theme, 'mediaName'));
        $this->assertTrue(property_exists($theme, 'mediaType'));
        $this->assertTrue(property_exists($theme, 'mediaSize'));
        $this->assertTrue(property_exists($theme, 'mediaTmpName'));
        $this->assertTrue(property_exists($theme, 'mediaError'));
        $this->assertSame('', $theme->mediaRealType);
        $this->assertSame($upload_dir, $theme->uploadDir);
        $this->assertSame($allowed_mime_types, $theme->allowedMimeTypes);
        $this->assertSame(array('application/x-httpd-php'), $theme->deniedMimeTypes);
        $this->assertSame(0, $theme->maxFileSize);
        $this->assertTrue(property_exists($theme, 'maxWidth'));
        $this->assertTrue(property_exists($theme, 'maxHeight'));
        $this->assertTrue(property_exists($theme, 'targetFileName'));
        $this->assertTrue(property_exists($theme, 'prefix'));
        $this->assertSame(array(), $theme->errors);
        $this->assertTrue(property_exists($theme, 'savedDestination'));
        $this->assertTrue(property_exists($theme, 'savedFileName'));
        //$this->assertTrue(is_array($theme->extensionToMime));
        $this->assertSame(true, $theme->checkImageType);
        $extensionsToBeSanitized = array(
            'php', 'phtml', 'phtm', 'php3', 'php4', 'cgi', 'pl', 'asp', 'php5'
        );
        $this->assertSame($extensionsToBeSanitized, $theme->extensionsToBeSanitized);
        $imageExtensions = array(
            1 => 'gif', 2 => 'jpg', 3 => 'png', 4 => 'swf', 5 => 'psd', 6 => 'bmp', 7 => 'tif', 8 => 'tif', 9 => 'jpc',
            10 => 'jp2', 11 => 'jpx', 12 => 'jb2', 13 => 'swf', 14 => 'iff', 15 => 'wbmp', 16 => 'xbm'
        );
        $this->assertSame($imageExtensions, $theme->imageExtensions);
    }

    public function test___construct100() {
        $upload_dir = 'upload_dir';
        $allowed_mime_types = array('toto');
        $maxFileSize = 71;
        $maxWidth = 72;
        $maxHeight = 73;
        $theme = new  $this->myclass($upload_dir, $allowed_mime_types,$maxFileSize,$maxWidth,$maxHeight);
        $this->assertInstanceOf($this->myclass, $theme);
        $this->assertSame($maxFileSize, $theme->maxFileSize);
        $this->assertSame($maxWidth, $theme->maxWidth);
        $this->assertSame($maxHeight, $theme->maxHeight);
    }

    public function test_fetchMedia()
    {
        //$upload_dir = 'upload_dir';
        //$allowed_mime_types = array('toto');
        //$theme = new  $this->myclass($upload_dir, $allowed_mime_types);
        // fetchMedia
        $this->markTestSkipped('');
    }

    public function test_setTargetFileName()
    {
        $upload_dir = 'upload_dir';
        $allowed_mime_types = array('toto');
        $theme = new  $this->myclass($upload_dir, $allowed_mime_types);
        $nom = 'toto';
        $theme->setTargetFileName('  '.$nom.'  ');
        $this->assertSame($nom, $theme->targetFileName);
    }

    public function test_setPrefix()
    {
        $upload_dir = 'upload_dir';
        $allowed_mime_types = array('toto');
        $theme = new  $this->myclass($upload_dir, $allowed_mime_types);
        $nom = 'toto';
        $theme->setPrefix('  '.$nom.'  ');
        $this->assertSame($nom, $theme->prefix);
    }

    public function test_getMediaName()
    {
        $upload_dir = 'upload_dir';
        $allowed_mime_types = array('toto');
        $theme = new  $this->myclass($upload_dir, $allowed_mime_types);
        $name = 'titi';
        $theme->mediaName = $name;
        $value = $theme->getMediaName();
        $this->assertSame($name, $value);
    }

    public function test_getMediaType()
    {
        $upload_dir = 'upload_dir';
        $allowed_mime_types = array('toto');
        $theme = new  $this->myclass($upload_dir, $allowed_mime_types);
        $name = 'titi';
        $theme->mediaType = $name;
        $value = $theme->getMediaType();
        $this->assertSame($name, $value);
    }

    public function test_getMediaSize()
    {
        $upload_dir = 'upload_dir';
        $allowed_mime_types = array('toto');
        $theme = new  $this->myclass($upload_dir, $allowed_mime_types);
        $size = 71;
        $theme->mediaSize = $size;
        $value = $theme->getMediaSize();
        $this->assertSame($size, $value);
    }

    public function test_getMediaTmpName()
    {
        $upload_dir = 'upload_dir';
        $allowed_mime_types = array('toto');
        $theme = new  $this->myclass($upload_dir, $allowed_mime_types);
        $name = 'titi';
        $theme->mediaTmpName = $name;
        $value = $theme->getMediaTmpName();
        $this->assertSame($name, $value);
    }

    public function test_getSavedFileName()
    {
        $upload_dir = 'upload_dir';
        $allowed_mime_types = array('toto');
        $theme = new  $this->myclass($upload_dir, $allowed_mime_types);
        $name = 'titi';
        $theme->savedFileName = $name;
        $value = $theme->getSavedFileName();
        $this->assertSame($name, $value);
    }

    public function test_getSavedDestination()
    {
        $upload_dir = 'upload_dir';
        $allowed_mime_types = array('toto');
        $theme = new  $this->myclass($upload_dir, $allowed_mime_types);
        $name = 'titi';
        $theme->savedDestination = $name;
        $value = $theme->getSavedDestination();
        $this->assertSame($name, $value);
    }

    public function test_upload()
    {
        //$upload_dir = 'upload_dir';
        //$allowed_mime_types = array('toto');
        //$theme = new  $this->myclass($upload_dir, $allowed_mime_types);
        // upload
        $this->markTestSkipped('');
    }

    public function test__copyFile()
    {
        //$upload_dir = 'upload_dir';
        //$allowed_mime_types = array('toto');
        //$theme = new  $this->myclass($upload_dir, $allowed_mime_types);
        // _copyFile
        $this->markTestSkipped('');
    }

    public function test_checkMaxFileSize()
    {
        //$upload_dir = 'upload_dir';
        //$allowed_mime_types = array('toto');
        //$theme = new  $this->myclass($upload_dir, $allowed_mime_types);
        // checkMaxFileSize
        $this->markTestSkipped('');
    }

    public function test_checkMaxWidth()
    {
        //$upload_dir = 'upload_dir';
        //$allowed_mime_types = array('toto');
        //$theme = new  $this->myclass($upload_dir, $allowed_mime_types);
        // checkMaxWidth
        $this->markTestSkipped('');
    }

    public function test_checkMaxHeight()
    {
        //$upload_dir = 'upload_dir';
        //$allowed_mime_types = array('toto');
        //$theme = new  $this->myclass($upload_dir, $allowed_mime_types);
        // checkMaxHeight
        $this->markTestSkipped('');
    }

    public function test_checkMimeType()
    {
        //$upload_dir = 'upload_dir';
        //$allowed_mime_types = array('toto');
        //$theme = new  $this->myclass($upload_dir, $allowed_mime_types);
        // checkMimeType
        $this->markTestSkipped('');
    }

    public function test_checkImageType()
    {
        //$upload_dir = 'upload_dir';
        //$allowed_mime_types = array('toto');
        //$theme = new  $this->myclass($upload_dir, $allowed_mime_types);
        // checkImageType
        $this->markTestSkipped('');
    }

    public function test_sanitizeMultipleExtensions()
    {
        //$upload_dir = 'upload_dir';
        //$allowed_mime_types = array('toto');
        //$theme = new  $this->myclass($upload_dir, $allowed_mime_types);
        // sanitizeMultipleExtensions
        $this->markTestSkipped('');
    }

    public function test_setErrors()
    {
        //$upload_dir = 'upload_dir';
        //$allowed_mime_types = array('toto');
        //$theme = new  $this->myclass($upload_dir, $allowed_mime_types);
        // setErrors
        $this->markTestSkipped('');
    }

    public function test_getErrors()
    {
        //$upload_dir = 'upload_dir';
        //$allowed_mime_types = array('toto');
        //$theme = new  $this->myclass($upload_dir, $allowed_mime_types);
        // getErrors
        $this->markTestSkipped('');
    }

}
