<?php
namespace Xoops\Test\Core\Service\Data;

use Xoops\Core\Service\Data\EmailAddress;

class EmailAddressTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EmailAddress
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new EmailAddress();
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
        $this->assertInstanceOf(EmailAddress::class, $this->object);
    }

    public function testNewEmailAddressWithArguments()
    {
        $address = new EmailAddress('user@example.com', 'name');
        $this->assertInstanceOf('\Xoops\Core\Service\Data\EmailAddress', $address);
        $this->assertEquals('user@example.com', $address->getEmail());
        $this->assertEquals('name', $address->getDisplayName());
    }

    public function testNewEmailAddressWithFluent()
    {
        $actual = $this->object->withEmail('user@example.com')->withDisplayName('name');
        $this->assertNotSame($this->object, $actual);
        $this->assertEquals('user@example.com', $actual->getEmail());
        $this->assertEquals('name', $actual->getDisplayName());
        $this->expectException(\LogicException::class);
        $this->object->getEmail();
    }

    public function testNewEmailAddressBadArguments()
    {
        $this->expectException(\InvalidArgumentException::class);
        $address = new EmailAddress('fred');
    }

    public function testGetEmailNotSet()
    {
        try {
            $this->object->getEmail();
        } catch (\LogicException $e) {
            $this->assertStringContainsString('Email', $e->getMessage());
        }
        $this->assertInstanceOf(\LogicException::class, $e);
    }

    public function testGetDisplayNameNotSet()
    {
        $actual = $this->object->getDisplayName();
        $this->assertNull($actual);
    }

    public function testGetDisplayNameInvalid()
    {
        $address = new class() extends EmailAddress
        {
            public function __construct()
            {
                parent::__construct();
                $this->displayName = 12;
            }
        };

        try {
            $address->getDisplayName();
        } catch (\LogicException $e) {
            $this->assertStringContainsString('Display', $e->getMessage());
        }
        $this->assertInstanceOf(\LogicException::class, $e);
    }
}
