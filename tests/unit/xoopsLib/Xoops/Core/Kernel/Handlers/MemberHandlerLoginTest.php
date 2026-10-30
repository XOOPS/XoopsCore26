<?php
require_once(__DIR__.'/../../../../../init_new.php');

use Xoops\Core\Kernel\Handlers\XoopsMemberHandler;
use Xoops\Core\Kernel\Handlers\XoopsUser;

/**
 * loginUser() against a legacy MD5 password hash, without a database
 */
class MemberHandlerLoginTest extends \PHPUnit\Framework\TestCase
{
    private function handlerForHash($hash)
    {
        $user = new XoopsUser();
        $user->setVar('pass', $hash);
        $users = new class(array($user)) {
            private $users;
            public $inserted = 0;
            public function __construct($users)
            {
                $this->users = $users;
            }
            public function getObjects($criteria = null, $idAsKey = false)
            {
                return $this->users;
            }
            public function insert($user)
            {
                ++$this->inserted;
                return true;
            }
        };
        $handler = (new ReflectionClass(XoopsMemberHandler::class))->newInstanceWithoutConstructor();
        $property = new ReflectionProperty(XoopsMemberHandler::class, 'userHandler');
        $property->setAccessible(true);
        $property->setValue($handler, $users);
        return array($handler, $users);
    }

    public function testLoginUserLegacyMd5LogsInAndRehashes()
    {
        list($handler, $users) = $this->handlerForHash(md5('secret'));
        $user = $handler->loginUser('someone', 'secret');
        $this->assertInstanceOf(XoopsUser::class, $user);
        $this->assertSame(1, $users->inserted);
        $this->assertTrue(password_verify('secret', $user->pass()));
    }

    public function testLoginUserCurrentHashLogsInWithoutRehash()
    {
        list($handler, $users) = $this->handlerForHash(password_hash('secret', PASSWORD_DEFAULT));
        $this->assertInstanceOf(XoopsUser::class, $handler->loginUser('someone', 'secret'));
        $this->assertSame(0, $users->inserted);
    }

    public function testLoginUserCurrentHashWrongPasswordIsRejected()
    {
        list($handler, $users) = $this->handlerForHash(password_hash('secret', PASSWORD_DEFAULT));
        $this->assertFalse($handler->loginUser('someone', 'Secret'));
        $this->assertSame(0, $users->inserted);
    }

    public function testLoginUserLegacyMd5MagicHashCollisionIsRejected()
    {
        // md5('240610708') and md5('QNKCDZO') both look like 0e<digits>, equal under ==
        list($handler, $users) = $this->handlerForHash(md5('240610708'));
        $this->assertFalse($handler->loginUser('someone', 'QNKCDZO'));
        $this->assertSame(0, $users->inserted);
    }
}
