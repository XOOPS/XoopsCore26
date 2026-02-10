<?php
require_once(__DIR__.'/../../../init_new.php');

use Xoops\Core\FixedGroups;

class FixedGroupsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FixedGroups
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        // $this->object = new FixedGroups();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }
    
    public function testConstants()
    {
        $this->assertTrue(is_numeric(FixedGroups::ADMIN));
        $this->assertTrue(is_numeric(FixedGroups::USERS));
        $this->assertTrue(is_numeric(FixedGroups::ANONYMOUS));
        $this->assertTrue(is_numeric(FixedGroups::REMOVED));
    }
}
