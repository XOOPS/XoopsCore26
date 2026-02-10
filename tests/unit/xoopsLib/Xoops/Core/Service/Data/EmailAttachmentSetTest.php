<?php
namespace Xoops\Test\Core\Service\Data;

use Xoops\Core\Service\Data\EmailAttachment;
use Xoops\Core\Service\Data\EmailAttachmentSet;

class EmailAttachmentSetTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EmailAttachmentSet
     */
    protected $object;

    protected const TEST_FILE = __DIR__ . '/test.png';

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new EmailAttachmentSet();
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
        $this->assertInstanceOf(EmailAttachmentSet::class, $this->object);
    }

    public function testNewEmailAttachmentSetWithArguments()
    {
        $attachmentArray[] = new EmailAttachment(static::TEST_FILE);

        $list = new EmailAttachmentSet($attachmentArray);
        $newAttachmentArray = $list->getAttachments();
        $this->assertSame($attachmentArray[0], $newAttachmentArray[0]);
    }

    public function testNewEmailAttachmentSetWithBadArguments()
    {
        $attachmentArray[] = new EmailAttachment();
        $this->expectException(\InvalidArgumentException::class);
        $list = new EmailAttachmentSet($attachmentArray);
    }

    public function testNewEmailAttachmentSetWithFluent()
    {
        $attachmentArray[] = new EmailAttachment(static::TEST_FILE);
        $attachmentArray[] = (new EmailAttachment())->withStringBody("This is a test body.\n");
        $actual = $this->object->withAddedAttachments($attachmentArray);
        $this->assertNotSame($this->object, $actual);

        $actualAttachments = $actual->getAttachments();
        $this->assertCount(2, $actualAttachments);
        $this->assertEquals(static::TEST_FILE, $actualAttachments[0]->getFilename());
        $this->assertNull($actualAttachments[1]->getFilename());

        $this->expectException(\LogicException::class);
        $this->object->getAttachments();
    }

    public function testWithBadAddedAttachments()
    {
        $attachmentArray[] = new EmailAttachment();
        $this->expectException(\InvalidArgumentException::class);
        $this->object->withAddedAttachments($attachmentArray);
    }

    public function testGetEachAttachment()
    {
        $attachmentArray[] = (new EmailAttachment())->withStringBody('body1');
        $attachmentArray[] = (new EmailAttachment())->withStringBody('body2');
        $attachmentArray[] = (new EmailAttachment())->withStringBody('body3');
        $list = $this->object->withAddedAttachments($attachmentArray);
        $count = 0;
        foreach ($list->getEachAttachment() as $attachment) {
            $this->assertInstanceOf(EmailAttachment::class, $attachment);
            ++$count;
            $this->assertStringContainsString((string) $count, $attachment->getStringBody());
        }
        $this->assertEquals(3, $count);
    }

    public function testGetEachAttachmentException()
    {
        $count = 0;
        try {
            foreach ($this->object->getEachAttachment() as $attachment) {
                ++$count;
            }
        } catch (\LogicException $e) {
        }
        $this->assertEquals(0, $count);
    }
}
