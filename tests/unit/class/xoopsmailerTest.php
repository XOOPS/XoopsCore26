<?php
require_once(__DIR__.'/../init_new.php');

use Xoops\Core\Kernel\Handlers\XoopsGroup;
use Xoops\Core\Kernel\Handlers\XoopsUser;

class XoopsMailerTest extends \PHPUnit\Framework\TestCase
{
    use DatabaseTestTrait;

    protected $myclass = 'XoopsMailer';
    protected $object = null;

    public function setUp(): void
    {
        $this->requireDatabase();
        $this->object = new $this->myclass();
    }

    protected function getPropertyValue($name)
    {
        $prop = new ReflectionProperty(get_class($this->object), $name);
        $prop->setAccessible(true);
        return $prop->getValue($this->object);
    }

    public function test___construct()
    {
        $instance = $this->object;
        $this->assertInstanceOf('XoopsMailer', $instance);

        $prop = $this->getPropertyValue('multimailer');
        $this->assertTrue(is_object($prop));
    }

    public function test_setHTML()
    {
        $instance = $this->object;

        $instance->setHTML(true);
        $x = $this->getPropertyValue('multimailer');
        $this->assertSame('text/html', $x->ContentType);

        $instance->setHTML(false);
        $x = $this->getPropertyValue('multimailer');
        $this->assertSame('text/plain', $x->ContentType);
    }

    public function test_reset()
    {
        $instance = $this->object;

        $instance->reset();

        $x = $this->getPropertyValue('fromEmail');
        $this->assertSame('', $x);
        $x = $this->getPropertyValue('fromName');
        $this->assertSame('', $x);
        $x = $this->getPropertyValue('fromUser');
        $this->assertSame(null, $x);
        $x = $this->getPropertyValue('priority');
        $this->assertSame('', $x);
        $x = $this->getPropertyValue('toUsers');
        $this->assertSame(array(), $x);
        $x = $this->getPropertyValue('toEmails');
        $this->assertSame(array(), $x);
        $x = $this->getPropertyValue('headers');
        $this->assertSame(array(), $x);
        $x = $this->getPropertyValue('subject');
        $this->assertSame('', $x);
        $x = $this->getPropertyValue('body');
        $this->assertSame('', $x);
        $x = $this->getPropertyValue('errors');
        $this->assertSame(array(), $x);
        $x = $this->getPropertyValue('success');
        $this->assertSame(array(), $x);
        $x = $this->getPropertyValue('isMail');
        $this->assertSame(false, $x);
        $x = $this->getPropertyValue('isPM');
        $this->assertSame(false, $x);
        $x = $this->getPropertyValue('assignedTags');
        $this->assertSame(array(), $x);
        $x = $this->getPropertyValue('template');
        $this->assertSame('', $x);
        $x = $this->getPropertyValue('templatedir');
        $this->assertSame('', $x);
        $x = $this->getPropertyValue('LE');
        $this->assertSame("\n", $x);
    }

    public function test_setTemplateDir()
    {
        $instance = $this->object;

        $instance->setTemplateDir();
        $x = $this->getPropertyValue('templatedir');
        $this->assertSame('', $x);

        $instance->setTemplateDir('aa' . DIRECTORY_SEPARATOR . 'bb');
        $x = $this->getPropertyValue('templatedir');
        $this->assertSame('aa/bb', $x);
    }

    public function test_setTemplate()
    {
        $instance = $this->object;

        $value = 'value';
        $instance->setTemplate($value);
        $x = $this->getPropertyValue('template');
        $this->assertSame($value, $x);
    }

    public function test_setFromEmail()
    {
        $instance = $this->object;

        $value = ' value ';
        $instance->setFromEmail($value);
        $x = $this->getPropertyValue('fromEmail');
        $this->assertSame(trim($value), $x);
    }

    public function test_setFromName()
    {
        $instance = $this->object;

        $value = ' value ';
        $instance->setFromName($value);
        $x = $this->getPropertyValue('fromName');
        $this->assertSame(trim($value), $x);
    }

    public function test_setFromUser()
    {
        $instance = $this->object;

        $value = new XoopsUser();
        $instance->setFromUser($value);
        $x = $this->getPropertyValue('fromUser');
        $this->assertSame($value, $x);
    }

    public function test_setPriority()
    {
        $instance = $this->object;

        $value = ' value ';
        $instance->setPriority($value);
        $x = $this->getPropertyValue('priority');
        $this->assertSame(trim($value), $x);
    }

    public function test_setSubject()
    {
        $instance = $this->object;

        $value = ' value ';
        $instance->setSubject($value);
        $x = $this->getPropertyValue('subject');
        $this->assertSame(trim($value), $x);
    }

    public function test_setBody()
    {
        $instance = $this->object;

        $value = ' value ';
        $instance->setBody($value);
        $x = $this->getPropertyValue('body');
        $this->assertSame(trim($value), $x);
    }

    public function test_useMail()
    {
        $instance = $this->object;

        $instance->useMail();
        $x = $this->getPropertyValue('isMail');
        $this->assertSame(true, $x);
    }

    public function test_usePM()
    {
        $instance = $this->object;

        $instance->usePM();
        $x = $this->getPropertyValue('isPM');
        $this->assertSame(true, $x);
    }

    public function test_send()
    {
        $this->markTestincomplete();
    }

    public function test_getErrors()
    {
        $instance = $this->object;

        $errors = array('message1','message2','message3');
        $prop = new ReflectionProperty(get_class($this->object), 'errors');
        $prop->setAccessible(true);
        $prop->setValue($this->object, $errors);

        $x = $instance->getErrors(false);
        $this->assertSame($errors, $x);

        $x = $instance->getErrors(true);
        $this->assertTrue(is_string($x));
        $this->assertTrue(preg_match('#<h4>.*</h4>.*<br />#', $x) > 0);

        $prop = new ReflectionProperty(get_class($this->object), 'errors');
        $prop->setAccessible(true);
        $prop->setValue($this->object, null);

        $x = $instance->getErrors(true);
        $this->assertSame('', $x);

        $x = $instance->getErrors(false);
        $this->assertSame(null, $x);
    }

    public function test_getSuccess()
    {
        $instance = $this->object;

        $success = array('message1','message2','message3');
        $prop = new ReflectionProperty(get_class($this->object), 'success');
        $prop->setAccessible(true);
        $prop->setValue($this->object, $success);

        $x = $instance->getSuccess(false);
        $this->assertSame($success, $x);

        $x = $instance->getSuccess(true);
        $this->assertTrue(is_string($x));
        $this->assertTrue(preg_match('#.*<br />#', $x) > 0);

        $prop = new ReflectionProperty(get_class($this->object), 'success');
        $prop->setAccessible(true);
        $prop->setValue($this->object, null);

        $x = $instance->getSuccess(true);
        $this->assertSame('', $x);

        $x = $instance->getSuccess(false);
        $this->assertSame(null, $x);
    }

    public function test_assign()
    {
        $instance = $this->object;

        $tag = '  tag  ';
        $value = 'value';
        $instance->assign($tag, $value);
        $x = $this->getPropertyValue('assignedTags');
        $this->assertSame($value, $x[strtoupper(trim($tag))]);

        $tags = array('  tag1  ' => 'value1', '  tag2  ' => 'value2');
        $instance->assign($tags);
        $x = $this->getPropertyValue('assignedTags');
        $this->assertSame('value1', $x['TAG1']);
        $this->assertSame('value2', $x['TAG2']);
    }

    public function test_addHeaders()
    {
        $instance = $this->object;

        $value = ' value ';
        $instance->addHeaders($value);
        $x = $this->getPropertyValue('headers');
        $le = $this->getPropertyValue('LE');
        $this->assertSame(trim($value).$le, $x[0]);
    }

    public function test_setToEmails()
    {
        $instance = $this->object;

        $emails = array('email@email.com','email@email.com');
        $instance->setToEmails($emails);
        $x = $this->getPropertyValue('toEmails');
        $this->assertSame($emails, $x);
    }

    public function test_setToUsers()
    {
        $instance = $this->object;

        $users = array(new XoopsUser(),new XoopsUser());
        $instance->setToUsers($users);
        $x = $this->getPropertyValue('toUsers');
        $this->assertSame($users, $x);
    }

    public function test_setToGroups()
    {
        $instance = $this->object;

        $group = new XoopsGroup();
        $group->assignVar('groupid', 1);
        $groups = array($group);
        $instance->setToGroups($groups);
        $x = $this->getPropertyValue('toUsers');
        $this->assertTrue(is_array($x));
        $this->assertInstanceOf('\\Xoops\\Core\\Kernel\\Handlers\\XoopsUser', $x[0]);
    }

    public function test_encodeFromName()
    {
        $instance = $this->object;

        $value = 'value';
        $x = $instance->encodeFromName($value);
        $this->assertSame($value, $x);
    }

    public function test_encodeSubject()
    {
        $instance = $this->object;

        $value = 'value';
        $x = $instance->encodeSubject($value);
        $this->assertSame($value, $x);
    }

    public function test_encodeBody()
    {
        $instance = $this->object;

        $value = 'value';
        $x = $instance->encodeBody($value);
        $this->assertSame(null, $x);
    }
}
