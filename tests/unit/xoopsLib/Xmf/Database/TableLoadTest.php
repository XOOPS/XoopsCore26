<?php
namespace Xmf\Database;

require_once(__DIR__.'/../../../init_new.php');

class TableLoadTest extends \PHPUnit\Framework\TestCase
{
    use \DatabaseTestTrait;

    /**
     * @var TableLoad
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->object = new TableLoad;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testLoadTableFromArray()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testLoadTableFromYamlFile()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testTruncateTable()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testRowCount()
    {
        $actual = $this->object->countRows('system_user');
        $this->assertTrue(is_integer($actual));
        $this->assertTrue($actual >= 1);
    }
}
