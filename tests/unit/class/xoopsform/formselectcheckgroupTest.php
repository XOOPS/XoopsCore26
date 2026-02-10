<?php
require_once(__DIR__.'/../../init_new.php');

class XoopsFormSelectCheckGroupTest extends \PHPUnit\Framework\TestCase
{
    use DatabaseTestTrait;

    protected $myClass = 'XoopsFormSelectCheckGroup';

    public function setUp(): void
    {
        $this->requireDatabase();
    }

    public function test___construct()
    {
        $instance = new $this->myClass('');
        $this->assertInstanceOf('Xoops\\Form\\GroupCheckbox', $instance);
    }
}
