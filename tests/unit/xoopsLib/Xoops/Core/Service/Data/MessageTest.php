<?php
namespace Xoops\Test\Core\Service\Data;

use Xoops\Core\Service\Data\Message;

class MessageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Message
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new Message();
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
        $this->assertInstanceOf(Message::class, $this->object);
    }

    public function testNewMessageWithArguments()
    {
        $message = new Message('subject', 'body', 2, 1);
        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals(1, $message->getToId());
        $this->assertEquals(2, $message->getFromId());
        $this->assertEquals('subject', $message->getSubject());
        $this->assertEquals('body', $message->getBody());
    }

    public function testWithToId()
    {
        $actual = $this->object->withToId(1);
        $this->assertInstanceOf(Message::class, $actual);
        $this->assertNotSame($this->object, $actual);
        $this->assertEquals(1, $actual->getToId());
    }

    public function testWithFromId()
    {
        $actual = $this->object->withFromId(2);
        $this->assertInstanceOf(Message::class, $actual);
        $this->assertNotSame($this->object, $actual);
        $this->assertEquals(2, $actual->getFromId());
    }

    public function testWithSubject()
    {
        $actual = $this->object->withSubject('subject');
        $this->assertInstanceOf(Message::class, $actual);
        $this->assertNotSame($this->object, $actual);
        $this->assertEquals('subject', $actual->getSubject());
    }

    public function testWithBody()
    {
        $actual = $this->object->withBody('body');
        $this->assertInstanceOf(Message::class, $actual);
        $this->assertNotSame($this->object, $actual);
        $this->assertEquals('body', $actual->getBody());
    }

    public function testNewMessageWithFluent()
    {
        $actual = $this->object->withToId(1)->withFromId(2)->withSubject('subject')->withBody('body');
        $this->assertNotSame($this->object, $actual);
        $this->assertEquals(1, $actual->getToId());
        $this->assertEquals(2, $actual->getFromId());
        $this->assertEquals('subject', $actual->getSubject());
        $this->assertEquals('body', $actual->getBody());
        $this->expectException(\LogicException::class);
        $this->object->getToId();
    }

    public function testNewMessageMissingArguments()
    {
        $message = new Message(null, null, 2);
        $this->assertEquals(2, $message->getFromId());
        $this->expectException(\LogicException::class);
        $message->getToId();
    }

    public function testNewMessageBadArguments()
    {
        $this->expectException(\InvalidArgumentException::class);
        $message = new Message(null, null, -1);
    }

    public function testWithToIdException()
    {
        try {
            $this->object->withToId('0');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('To', $e->getMessage());
        }
        $this->assertInstanceOf(\InvalidArgumentException::class, $e);
    }

    public function testWithFromIdException()
    {
        try {
            $this->object->withFromId(-88);
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('From', $e->getMessage());
        }
        $this->assertInstanceOf(\InvalidArgumentException::class, $e);
    }

    public function testWithSubjectException()
    {
        try {
            $this->object->withSubject(' ');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Subject', $e->getMessage());
        }
        $this->assertInstanceOf(\InvalidArgumentException::class, $e);
    }

    public function testWithBodyException()
    {
        try {
            $this->object->withBody("\n");
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Body', $e->getMessage());
        }
        $this->assertInstanceOf(\InvalidArgumentException::class, $e);
    }

    public function testGetToIdException()
    {
        try {
            $this->object->getToId();
        } catch (\LogicException $e) {
            $this->assertStringContainsString('To', $e->getMessage());
        }
        $this->assertInstanceOf(\LogicException::class, $e);
    }

    public function testGetFromIdException()
    {
        try {
            $this->object->getFromId();
        } catch (\LogicException $e) {
            $this->assertStringContainsString('From', $e->getMessage());
        }
        $this->assertInstanceOf(\LogicException::class, $e);
    }

    public function testGetSubjectException()
    {
        try {
            $this->object->getSubject();
        } catch (\LogicException $e) {
            $this->assertStringContainsString('Subject', $e->getMessage());
        }
        $this->assertInstanceOf(\LogicException::class, $e);
    }

    public function testGetBodyException()
    {
        try {
            $this->object->getBody();
        } catch (\LogicException $e) {
            $this->assertStringContainsString('Body', $e->getMessage());
        }
        $this->assertInstanceOf(\LogicException::class, $e);
    }
}
