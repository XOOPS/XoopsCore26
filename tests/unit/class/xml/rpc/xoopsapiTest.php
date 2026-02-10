<?php
require_once(__DIR__.'/../../../init_new.php');

use Xoops\Core\Kernel\Handlers\XoopsModule;

class XoopsApiTest extends \PHPUnit\Framework\TestCase
{
    use DatabaseTestTrait;

    protected $myclass = 'XoopsApi';

    public function setUp(): void
    {
        $this->requireDatabase();
    }

    public function test___construct()
    {
        $params = array('p1'=>'one');
        $response = new XoopsXmlRpcResponse();
        $module = new XoopsModule();
        $x = new $this->myclass($params, $response, $module);
        $this->assertInstanceof($this->myclass, $x);
        $this->assertInstanceof('XoopsXmlRpcApi', $x);
    }

    public function test_newPost()
    {
        $this->markTestIncomplete();
    }

    public function test_editPost()
    {
        $this->markTestIncomplete();
    }

    public function test_deletePost()
    {
        $this->markTestIncomplete();
    }

    public function test_getPost()
    {
        $this->markTestIncomplete();
    }

    public function test_getRecentPosts()
    {
        $this->markTestIncomplete();
    }

    public function test_getCategories()
    {
        $this->markTestIncomplete();
    }
}
