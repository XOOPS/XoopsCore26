<?php
namespace Xoops\Test\Core\Service\Data;

use Xoops\Core\Service\Data\EmailAddress;
use Xoops\Core\Service\Data\EmailAddressList;

class EmailAddressListTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EmailAddressList
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new EmailAddressList();
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
        $this->assertInstanceOf(EmailAddressList::class, $this->object);
    }

    public function testNewEmailAddressListWithArguments()
    {
        $addressArray[] = new EmailAddress('user@example.com', 'name');

        $list = new EmailAddressList($addressArray);
        $newAddressArray = $list->getAddresses();
        $this->assertSame($addressArray[0], $newAddressArray[0]);
    }

    public function testNewEmailAddressListWithBadArguments()
    {
        $addressArray[] = new EmailAddress();
        $this->expectException(\InvalidArgumentException::class);
        new EmailAddressList($addressArray);
    }

    public function testNewEmailAddressListWithFluent()
    {
        $addressArray[] = new EmailAddress('user@example.com', 'name');
        $addressArray[] = new EmailAddress('user2@example.com', 'name2');
        $actual = $this->object->withAddedAddresses($addressArray);
        $this->assertNotSame($this->object, $actual);

        $actualAddresses = $actual->getAddresses();
        $this->assertCount(2, $actualAddresses);
        $this->assertEquals('name', $actualAddresses[0]->getDisplayName());
        $this->assertEquals('user@example.com', $actualAddresses[0]->getEmail());

        $this->expectException(\LogicException::class);
        $this->object->getAddresses();
    }

    public function testWithBadAddedAddresses()
    {
        $addressArray[] = new EmailAddress();
        $this->expectException(\InvalidArgumentException::class);
        $this->object->withAddedAddresses($addressArray);
    }

    public function testGetEachAddress()
    {
        $addressArray[] = new EmailAddress('user1@example.com', 'name1');
        $addressArray[] = new EmailAddress('user2@example.com');
        $addressArray[] = new EmailAddress('user3@example.com', 'name2');
        $list = $this->object->withAddedAddresses($addressArray);
        $count = 0;
        foreach ($list->getEachAddress() as $address) {
            // do stuff with $address
            $this->assertInstanceOf(EmailAddress::class, $address);
            ++$count;
            $this->assertStringContainsString((string) $count, $address->getEmail());
        }
        $this->assertEquals(3, $count);
    }

    public function testGetEachAddressException()
    {
        $count = 0;
        try {
            foreach ($this->object->getEachAddress() as $address) {
                ++$count;
            }
        } catch (\LogicException $e) {
        }
        $this->assertEquals(0, $count);
    }
}
