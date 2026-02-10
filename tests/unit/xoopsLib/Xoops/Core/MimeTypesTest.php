<?php
require_once(__DIR__.'/../../../init_new.php');

use \Xoops\Core\MimeTypes;

class MimeTypesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MimeTypes
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        // $this->object = new \Xoops\Core\MimeTypes;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testFindExtensions()
    {
        $this->assertTrue(in_array('txt', MimeTypes::findExtensions('text/plain')), 'match .txt extension');
        $matches = MimeTypes::findExtensions('image/jpeg');
        $this->assertTrue(in_array('jpg', $matches), 'match .jpg extension');
        $this->assertTrue(in_array('jpeg', $matches), 'match .jpeg extension');
		$x = MimeTypes::findExtensions('failme-no-such-type/no-such-subtype');
        $this->assertTrue(empty($x), 'match garbage mimetype');
    }

    public function testFindType()
    {
        $this->assertEquals('text/plain', MimeTypes::findType('txt'), 'get mimetype for .txt extension');
        $this->assertEquals('image/jpeg', MimeTypes::findType('jpg'), 'get mimetype for .jpg extension');
        $this->assertEquals('image/jpeg', MimeTypes::findType('jpeg'), 'get mimetype for .jpeg extension');
        $this->assertNull(MimeTypes::findType('failme-no-such-extension'), 'get mimetype for garbage extension');
    }
}
