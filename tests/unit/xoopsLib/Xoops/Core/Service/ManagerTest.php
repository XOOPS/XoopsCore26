<?php
require_once(__DIR__.'/../../../../init_new.php');

class Service_ManagerTest extends \PHPUnit\Framework\TestCase
{
    protected $myClass = '\Xoops\Core\Service\Manager';
    protected $object = null;

    public function setUp(): void
    {
        $class = $this->myClass;
        $this->object = $class::getInstance();
    }

    public function test_getInstance()
    {
        $instance = $this->object;
        $this->assertInstanceOf($this->myClass, $instance);

        $class = $this->myClass;
        $instance2 = $class::getInstance();
        $this->assertSame($instance, $instance2);
    }

    public function test_constants()
    {
        $instance = $this->object;

        $this->assertTrue(is_int($instance::MODE_EXCLUSIVE));
        $this->assertTrue(is_int($instance::MODE_CHOICE));
        $this->assertTrue(is_int($instance::MODE_PREFERENCE));
        $this->assertTrue(is_int($instance::MODE_MULTIPLE));

        $this->assertTrue(is_int($instance::PRIORITY_SELECTED));
        $this->assertTrue(is_int($instance::PRIORITY_HIGH));
        $this->assertTrue(is_int($instance::PRIORITY_MEDIUM));
        $this->assertTrue(is_int($instance::PRIORITY_LOW));
    }

    // function test_saveChoice()
    // {
    // $instance = $this->object;

    // $service = 'Avatar';
    // $provider = $instance->locate($service);
    // $this->assertTrue(is_object($provider));
    // $xoops_root_path = \XoopsBaseConfig::get('root-path');
    // if (! class_exists('AvatarsProvider',false)) {
    // require $xoops_root_path.'/modules/avatars/class/AvatarsProvider.php';
    // }
    // $ap = new AvatarsProvider();
    // $this->assertTrue(is_object($ap));
    // $provider->register($ap);

    // $choices = array('avatars' => $instance::PRIORITY_HIGH);
    // $instance->saveChoice($service,$choices);
    // $values = $instance->listChoices($service);
    // $this->assertTrue(is_array($values));
    // $this->assertTrue(is_object($values[0]));
    // $this->assertSame($instance::PRIORITY_HIGH, $values[0]->getPriority());

    // }
}
