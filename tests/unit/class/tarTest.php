<?php
require_once(__DIR__.'/../init_new.php');

class tarTest extends \PHPUnit\Framework\TestCase
{
    protected $myClass = 'tar';
    
    public function test___construct()
    {
        $x = new $this->myClass();
        $this->assertInstanceOf($this->myClass, $x);
    }
}
