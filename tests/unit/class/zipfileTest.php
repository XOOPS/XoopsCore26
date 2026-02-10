<?php
require_once(__DIR__.'/../init_new.php');

class zipfileTest extends \PHPUnit\Framework\TestCase
{
    protected $myClass = 'zipfile';

    public function test___construct()
    {
        $x = new $this->myClass();
        $this->assertInstanceOf($this->myClass, $x);
    }
}
