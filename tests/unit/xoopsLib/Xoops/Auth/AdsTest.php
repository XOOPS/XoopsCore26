<?php
require_once(__DIR__.'/../../../init_new.php');

class Xoops_Auth_AdsTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = '\Xoops\Auth\Ads';

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        if (!extension_loaded('ldap')) $this->markTestSkipped();
    }

    public function test___construct()
    {
        $instance = new $this->myclass();
        $this->assertInstanceOf($this->myclass, $instance);
    }

    public function test_authenticate()
    {
        $this->markTestIncomplete();
    }

    public function test_getUPN()
    {
        $this->markTestIncomplete();
    }
}
