<?php
namespace Xmf\Database;

require_once(__DIR__.'/../../../init_new.php');

class MigrateTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Migrate
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        try {
            $this->object = new Migrate('page');
        } catch (\Exception $e) {
            $this->markTestSkipped('Migrate requires a valid module and database: ' . $e->getMessage());
        }
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testContracts()
    {
        $this->assertInstanceOf('\Xmf\Database\Migrate', $this->object);
    }

    public function testSaveCurrentSchema()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testGetCurrentSchema()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testGetTargetDefinitions()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testSynchronizeSchema()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testGetSynchronizeDDL()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testGetLastError()
    {
        $actual = $this->object->getLastError();
        $this->assertNull($actual);
    }

    public function testGetLastErrNo()
    {
        $actual = $this->object->getLastErrNo();
        $this->assertNull($actual);
    }
}
