<?php
require_once(__DIR__.'/../init_new.php');

require_once(XOOPS_TU_ROOT_PATH . '/kernel/member.php');

class legacy_memberTest extends \PHPUnit\Framework\TestCase
{

    protected $conn = null;

    public function setUp(): void
    {
        $this->conn = Xoops::getInstance()->db();
    }

    public function test___construct()
    {
        $instance=new \XoopsMemberHandler($this->conn);
        $this->assertInstanceOf('\Xoops\Core\Kernel\Handlers\XoopsMemberHandler', $instance);
    }
}
