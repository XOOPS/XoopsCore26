<?php
require_once(__DIR__.'/../init_new.php');

require_once(XOOPS_TU_ROOT_PATH . '/kernel/object.php');

class Legacy_XoopsPersistableObjectHandlerTestInstance extends \XoopsPersistableObjectHandler
{
    // allow access to the protected function in abstract class
    public function __construct(
        \Xoops\Core\Database\Connection $db,
        $table = '',
        $className = '',
        $keyName = '',
        $identifierName = ''
    ) {
        parent::__construct($db, $table, $className, $keyName, $identifierName);
    }
}

class Legacy_XoopsPersistableObjectHandlerTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = 'Legacy_XoopsPersistableObjectHandlerTestInstance';

    public function test___publicProperties()
    {
        $items = array('db');
        foreach ($items as $item) {
            $prop = new ReflectionProperty($this->myclass, $item);
            $this->assertTrue($prop->isPublic());
        }
    }

    public function test___construct()
    {
        $conn = \Xoops\Core\Database\Factory::getConnection();
        $table = 'table';
        $className = 'className';
        $keyName = 'keyName';
        $identifierName = 'identifierName';
        $instance = new $this->myclass($conn, $table, $className, $keyName, $identifierName);
        $this->assertInstanceOf($this->myclass, $instance);
        $this->assertInstanceOf('Xoops\Core\Kernel\XoopsPersistableObjectHandler', $instance);
    }
}
