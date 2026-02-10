<?php
require_once(__DIR__.'/../../../../../init_new.php');

use Xoops\Core\Kernel\Handlers\XoopsUser;

class UserTest extends \PHPUnit\Framework\TestCase
{
    use DatabaseTestTrait;

    protected $object;

    public function setUp(): void
    {
        $this->requireDatabase();
        $this->object = new XoopsUser();
    }

    public function testContracts()
    {
        $this->assertInstanceOf('\Xoops\Core\Kernel\Handlers\XoopsUser', $this->object);
        $this->assertInstanceOf('\Xoops\Core\Kernel\XoopsObject', $this->object);
    }

    public function test___construct()
    {
        $value=$this->object->getVars();
        $this->assertTrue(isset($value['uid']));
        $this->assertTrue(isset($value['name']));
        $this->assertTrue(isset($value['uname']));
        $this->assertTrue(isset($value['email']));
        $this->assertTrue(isset($value['url']));
        $this->assertTrue(isset($value['user_avatar']));
        $this->assertTrue(isset($value['user_regdate']));
        $this->assertTrue(isset($value['user_icq']));
        $this->assertTrue(isset($value['user_from']));
        $this->assertTrue(isset($value['user_sig']));
        $this->assertTrue(isset($value['user_viewemail']));
        $this->assertTrue(isset($value['actkey']));
        $this->assertTrue(isset($value['user_aim']));
        $this->assertTrue(isset($value['user_yim']));
        $this->assertTrue(isset($value['user_msnm']));
        $this->assertTrue(isset($value['pass']));
        $this->assertTrue(isset($value['posts']));
        $this->assertTrue(isset($value['attachsig']));
        $this->assertTrue(isset($value['rank']));
        $this->assertTrue(isset($value['level']));
        $this->assertTrue(isset($value['theme']));
        $this->assertTrue(isset($value['timezone']));
        $this->assertTrue(isset($value['last_login']));
        $this->assertTrue(isset($value['last_pass_change']));
        $this->assertTrue(isset($value['umode']));
        $this->assertTrue(isset($value['uorder']));
        $this->assertTrue(isset($value['notify_method']));
        $this->assertTrue(isset($value['notify_mode']));
        $this->assertTrue(isset($value['user_occ']));
        $this->assertTrue(isset($value['bio']));
        $this->assertTrue(isset($value['user_intrest']));
        $this->assertTrue(isset($value['user_mailok']));
    }

    public function test_isGuest()
    {
        $value=$this->object->isGuest();
        $this->assertSame(false, $value);
    }

    public function test_getUnameFromId()
    {
        $value1=XoopsUser::getUnameFromId(0);
        $this->assertSame(\Xoops::getInstance()->getConfig('anonymous'), $value1);
        $value=XoopsUser::getUnameFromId(1);
        $this->assertInternalType('string', $value);
        $this->assertNotSame($value, $value1);
    }

    public function test_incrementPost()
    {
        $value=$this->object->incrementPost();
        $this->assertSame('', $value);
    }

    public function test_getGroups()
    {
        $group=$this->object->getGroups();
        $value=$this->object->setGroups($group);
        $this->assertSame(null, $value);
    }

    public function test_groups()
    {
        $group1=$this->object->getGroups();
        $group2=$this->object->groups();
        $this->assertSame($group1, $group2);
    }

    public function test_isAdmin()
    {
        $value=$this->object->isAdmin();
        $this->assertSame(false, $value);
    }

    public function test_rank()
    {
        $value=$this->object->rank();
        $this->assertTrue($value===null || is_array($value));
    }

    public function test_isActive()
    {
        $value=$this->object->isActive();
        $this->assertSame(false, $value);
    }

    public function test_isOnline()
    {
        $value=$this->object->isOnline();
        $this->assertSame(false, $value);
    }

    public function test_uid()
    {
        $value=$this->object->uid();
        $this->assertSame(null, $value);
    }

    public function test_id()
    {
        $value=$this->object->id();
        $this->assertSame($this->object->uid(), $value);
    }

    public function test_name()
    {
        $value=$this->object->name();
        $this->assertSame(null, $value);
    }

    public function test_email()
    {
        $value=$this->object->email();
        $this->assertSame(null, $value);
    }

    public function test_url()
    {
        $value=$this->object->url();
        $this->assertSame(null, $value);
    }

    public function test_user_avatar()
    {
        $value=$this->object->user_avatar();
        $this->assertSame(null, $value);
    }

    public function test_user_regdate()
    {
        $value=$this->object->user_regdate();
        $this->assertSame(null, $value);
    }

    public function test_user_icq()
    {
        $value=$this->object->user_icq();
        $this->assertSame('', $value);
    }

    public function test_user_from()
    {
        $value=$this->object->user_from();
        $this->assertSame(null, $value);
    }

    public function test_user_sig()
    {
        $value=$this->object->user_sig();
        $this->assertSame(null, $value);
    }

    public function test_user_viewemail()
    {
        $value=$this->object->user_viewemail();
        $this->assertSame(0, $value);
    }

    public function test_actkey()
    {
        $value=$this->object->actkey();
        $this->assertSame(null, $value);
    }

    public function test_user_aim()
    {
        $value=$this->object->user_aim();
        $this->assertSame(null, $value);
    }

    public function test_user_yim()
    {
        $value=$this->object->user_yim();
        $this->assertSame(null, $value);
    }

    public function test_user_msnm()
    {
        $value=$this->object->user_msnm();
        $this->assertSame(null, $value);
    }

    public function test_pass()
    {
        $value=$this->object->pass();
        $this->assertSame(null, $value);
    }

    public function test_posts()
    {
        $value=$this->object->posts();
        $this->assertSame(null, $value);
    }

    public function test_attachsig()
    {
        $value=$this->object->attachsig();
        $this->assertSame(0, $value);
    }

    public function test_level()
    {
        $value=$this->object->level();
        $this->assertSame(0, $value);
    }

    public function test_theme()
    {
        $value=$this->object->theme();
        $this->assertSame(null, $value);
    }

    public function test_timezone()
    {
        $value=$this->object->timezone();
        $this->assertInstanceOf('\DateTimeZone', $value);
        $this->assertSame('UTC', $value->getName());
    }

    public function test_umode()
    {
        $value=$this->object->umode();
        $this->assertSame(null, $value);
    }

    public function test_uorder()
    {
        $value=$this->object->uorder();
        $this->assertSame(1, $value);
    }

    public function test_notify_method()
    {
        $value=$this->object->notify_method();
        $this->assertSame(1, $value);
    }

    public function test_notify_mode()
    {
        $value=$this->object->notify_mode();
        $this->assertSame(0, $value);
    }

    public function test_user_occ()
    {
        $value=$this->object->user_occ();
        $this->assertSame(null, $value);
    }

    public function test_bio()
    {
        $value=$this->object->bio();
        $this->assertSame(null, $value);
    }

    public function test_user_intrest()
    {
        $value=$this->object->user_intrest();
        $this->assertSame(null, $value);
    }
}
