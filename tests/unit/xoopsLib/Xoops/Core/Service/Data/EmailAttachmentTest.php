<?php
namespace Xoops\Test\Core\Service\Data;

use Xoops\Core\Service\Data\EmailAttachment;

class EmailAttachmentTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EmailAttachment
     */
    protected $object;

    protected const TEST_FILE = __DIR__ . '/test.png';

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new EmailAttachment();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testContract()
    {
        $this->assertInstanceOf(EmailAttachment::class, $this->object);
    }

    public function testConstruct()
    {
        $filename = static::TEST_FILE;
        $mimeType = 'image/png';
        $attachment = new EmailAttachment($filename);
        $this->assertEquals($filename, $attachment->getFilename());
        $this->assertNull($attachment->getMimeType());

        $attachment = new EmailAttachment($filename, $mimeType);
        $this->assertEquals($filename, $attachment->getFilename());
        $this->assertEquals($mimeType, $attachment->getMimeType());

        $attachment = new EmailAttachment(null, $mimeType);
        $this->assertEquals($mimeType, $attachment->getMimeType());

        $this->expectException(\InvalidArgumentException::class);
        $attachment = new EmailAttachment($filename, 'bogus');
    }

    public function testNewBadFilename()
    {
        $this->expectException(\InvalidArgumentException::class);
        $attachment = new EmailAttachment(__DIR__.'/bogus.file');
    }

    public function testWithFilename()
    {
        $filename = static::TEST_FILE;
        $attachment = $this->object->withFilename($filename);
        $this->assertNotSame($attachment, $this->object);
        $this->assertEquals($filename, $attachment->getFilename());
    }

    public function testFileOrBodySpecified()
    {
        $filename = static::TEST_FILE;
        $attachment = new EmailAttachment($filename);
        $this->assertEquals($filename, $attachment->getFilename());
        $this->assertNull($attachment->getStringBody());

        $body = "Dummy string body.\n";
        $attachment = $this->object->withStringBody($body);
        $this->assertNotSame($attachment, $this->object);
        $this->assertNull($attachment->getFilename());
        $this->assertEquals($body, $attachment->getStringBody());
    }

    public function testNullFilename()
    {
        $this->expectException(\LogicException::class);
        $this->object->getFilename();
    }

    public function testNullStringBody()
    {
        $this->expectException(\LogicException::class);
        $this->object->getStringBody();
    }

    public function testWithMimeType()
    {
        $mimeType = 'text/json+juice-1.2';
        $this->assertNull($this->object->getMimeType());
        $attachment=$this->object->withMimeType($mimeType);
        $this->assertSame($mimeType, $attachment->getMimeType());

        $this->expectException(\InvalidArgumentException::class);
        $attachment=$this->object->withMimeType('notamimetype');
    }

    public function testForcedBadMimeType()
    {
        $attachment = new class() extends EmailAttachment
        {
            public function __construct()
            {
                parent::__construct();
                $this->mimeType = '';
            }
        };
        $this->expectException(\LogicException::class);
        $attachment->getMimeType();
    }

    public function testWithName()
    {
        $name = 'testname';
        $this->assertNull($this->object->getName());
        $attachment = $this->object->withName($name);
        $this->assertSame($name, $attachment->getName());
    }

    public function testForcedBadName()
    {
        $attachment = new class() extends EmailAttachment
        {
            public function __construct()
            {
                parent::__construct();
                $this->name = '';
            }
        };
        $this->expectException(\LogicException::class);
        $attachment->getName();
    }

    public function testInlineAttribute()
    {
        $this->assertFalse($this->object->getInlineAttribute());
        $this->assertTrue($this->object->withInlineAttribute()->getInlineAttribute());
        $this->assertTrue($this->object->withInlineAttribute(true)->getInlineAttribute());
        $this->assertFalse($this->object->withInlineAttribute(false)->getInlineAttribute());
    }
}
