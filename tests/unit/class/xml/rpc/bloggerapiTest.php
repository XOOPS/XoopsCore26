<?php
require_once(__DIR__.'/../../../init_new.php');

use Xoops\Core\Kernel\Handlers\XoopsModule;

class MockBloggerApi extends \BloggerApi
{
    public function getModule()
    {
        return $this->module;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function _checkUser($username, $password)
    {
        $xoops = Xoops::getInstance();

        $member_handler = $xoops->getHandlerMember();
        $userObject = new \Xoops\Core\Kernel\Handlers\XoopsUser();
        $this->user = null;
        $this->admin = false;
        if ($username == 'admin' && $password == 'goodpassword') {
            $userObject['uid'] = 1;
            $userObject['name'] = ucfirst($username);
            $userObject['uname'] = $username;
            $userObject['name'] = $username.'_name';
            $userObject['email'] = $username.'@xoops.com';
            $userObject['url'] = 'http://localhost/'.$username;
            // etc.
            $this->user = $userObject;
            $this->admin = true;
            return true;
        } elseif ($username == 'reguser' && $password == 'goodpassword') {
            $userObject['uid'] = 99999;
            $userObject['name'] = ucfirst($username);
            $userObject['uname'] = $username;
            $userObject['name'] = $username.'_name';
            $userObject['email'] = $username.'@xoops.com';
            $userObject['url'] = 'http://localhost/'.$username;
            // etc.
            $this->user = $userObject;
            return true;
        }
        return false;
    }
}

class BloggerApiTest extends \PHPUnit\Framework\TestCase
{
    use DatabaseTestTrait;

    protected $myClass = 'MockBloggerApi';

    public function setUp(): void
    {
        $this->requireDatabase();
    }

    public function test___construct()
    {
        $params = array(null, null, 'admin', 'goodpassword');
        $response = new XoopsXmlRpcResponse();
        $module = new XoopsModule();
        $instance = new $this->myClass($params, $response, $module);
        $this->assertInstanceof('XoopsXmlRpcApi', $instance);
    }

    public function test_newPost()
    {
        $title = '<title>Title</title>';
        $hometext = '<hometext>Hometext</hometext>';
        $moretext = '<moretext>Moretext</moretext>';
        $categories = '<categories>10</categories>';
        $text = $title . $hometext . $moretext . $categories;

        $params = array('', '', 'admin', 'goodpassword', $text);
        $response = new XoopsXmlRpcResponse();
        $module = new XoopsModule();
        $instance = new $this->myClass($params, $response, $module);
        $instance->newPost();
        $msg = $response->render();
        if (false !== strpos($msg, '<name>faultString</name><value>Module not found')) {
            $this->markTestSkipped();
        }
        $this->markTestIncomplete();
    }

    public function test_editPost()
    {
        $title = '<title>Title</title>';
        $hometext = '<hometext>Hometext</hometext>';
        $moretext = '<moretext>Moretext</moretext>';
        $categories = '<categories>10</categories>';
        $text = $title . $hometext . $moretext . $categories;

        $params = array('', '', 'admin', 'goodpassword', $text);
        $response = new XoopsXmlRpcResponse();
        $module = new XoopsModule();
        $instance = new $this->myClass($params, $response, $module);
        $instance->editPost();
        $msg = $response->render();
        if (false !== strpos($msg, '<name>faultString</name><value>Module not found')) {
            $this->markTestSkipped();
        }
        $this->markTestIncomplete();
    }

    public function test_deletePost()
    {
        $title = '<title>Title</title>';
        $hometext = '<hometext>Hometext</hometext>';
        $moretext = '<moretext>Moretext</moretext>';
        $categories = '<categories>10</categories>';
        $text = $title . $hometext . $moretext . $categories;

        $params = array('', '', 'admin', 'goodpassword', $text);
        $response = new XoopsXmlRpcResponse();
        $module = new XoopsModule();
        $instance = new $this->myClass($params, $response, $module);
        $instance->deletePost();
        $msg = $response->render();
        if (false !== strpos($msg, '<name>faultString</name><value>Module not found')) {
            $this->markTestSkipped();
        }
        $this->markTestIncomplete();
    }

    public function test_getPost()
    {
        $title = '<title>Title</title>';
        $hometext = '<hometext>Hometext</hometext>';
        $moretext = '<moretext>Moretext</moretext>';
        $categories = '<categories>10</categories>';
        $text = $title . $hometext . $moretext . $categories;

        $params = array('', '', 'admin', 'goodpassword', $text);
        $response = new XoopsXmlRpcResponse();
        $module = new XoopsModule();
        $instance = new $this->myClass($params, $response, $module);
        $instance->getPost();
        $msg = $response->render();
        if (false !== strpos($msg, '<name>faultString</name><value>Module not found')) {
            $this->markTestSkipped();
        }
        $this->markTestIncomplete();
    }

    public function test_getRecentPosts()
    {
        $title = '<title>Title</title>';
        $hometext = '<hometext>Hometext</hometext>';
        $moretext = '<moretext>Moretext</moretext>';
        $categories = '<categories>10</categories>';
        $text = $title . $hometext . $moretext . $categories;

        $params = array('', '', 'admin', 'goodpassword', $text);
        $response = new XoopsXmlRpcResponse();
        $module = new XoopsModule();
        $instance = new $this->myClass($params, $response, $module);
        $instance->getRecentPosts();
        $msg = $response->render();
        if (false !== strpos($msg, '<name>faultString</name><value>Module not found')) {
            $this->markTestSkipped();
        }
        $this->markTestIncomplete();
    }

    public function test_getUsersBlogs()
    {
        $params = array(null, 'admin', 'WRONG_password');
        $response = new XoopsXmlRpcResponse();
        $module = new XoopsModule();
        $instance = new $this->myClass($params, $response, $module);

        $result = $instance->getUsersBlogs();
        $msg = $response->render();
        $expected = '<?xml version="1.0"?>'
            . '<methodResponse><fault><value><struct>'
            . '<member><name>faultCode</name><value>104</value></member>'
            . "<member><name>faultString</name><value>User authentication failed\n</value></member>"
            . '</struct></value></fault></methodResponse>';
        $this->assertSame($expected, $msg);


        $params = array(null, 'admin', 'goodpassword');
        $response = new XoopsXmlRpcResponse();
        $module = new XoopsModule();
        $instance = new $this->myClass($params, $response, $module);

        $result = $instance->getUsersBlogs();
        $msg = $response->render();
        $url = \XoopsBaseConfig::get('url').'/modules/'.$instance->getModule()->getVar('dirname').'/';
        $mid = $instance->getModule()->getVar('mid');
        $expected = '<?xml version="1.0"?><methodResponse><params><param><value><array><data><value><struct>'
            . '<member><name>url</name><value><string>'.$url.'</string></value></member>'
            . '<member><name>blogid</name><value><string>'.$mid.'</string></value></member>'
            . '<member><name>blogName</name><value><string>XOOPS Blog</string></value></member>'
            . '</struct></value></data></array></value></param></params></methodResponse>';
        $this->assertSame($expected, $msg);
    }

    public function test_getUserInfo()
    {
        $params = array(null, 'admin', 'WRONG_password');
        $response = new XoopsXmlRpcResponse();
        $module = new XoopsModule();
        $instance = new $this->myClass($params, $response, $module);

        $result = $instance->getUserInfo();
        $msg = $response->render();
        $expected = '<?xml version="1.0"?>'
            . '<methodResponse><fault><value><struct>'
            . '<member><name>faultCode</name><value>104</value></member>'
            . "<member><name>faultString</name><value>User authentication failed\n</value></member>"
            . '</struct></value></fault></methodResponse>';
        $this->assertSame($expected, $msg);

        $params = array(null, 'admin', 'goodpassword');
        $response = new XoopsXmlRpcResponse();
        $module = new XoopsModule();
        $instance = new $this->myClass($params, $response, $module);

        $result = $instance->getUserInfo();
        $msg = $response->render();
        $uname = $instance->getUser()->getVar('uname');
        $uid = $instance->getUser()->getVar('uid');
        $url = $instance->getUser()->getVar('url');
        $email = $instance->getUser()->getVar('email');
        $name = $instance->getUser()->getVar('name');
        $expected = '<?xml version="1.0"?><methodResponse><params><param><value><struct>'
            . '<member><name>nickname</name><value><string>'.$uname.'</string></value></member>'
            . '<member><name>userid</name><value><string>'.$uid.'</string></value></member>'
            . '<member><name>url</name><value><string>'.$url.'</string></value></member>'
            . '<member><name>email</name><value><string>'.$email.'</string></value></member>'
            . '<member><name>lastname</name><value><string></string></value></member>'
            . '<member><name>firstname</name><value><string>'.$name.'</string></value></member>'
            . '</struct></value></param></params></methodResponse>';
        $this->assertSame($expected, $msg);
    }

    public function test_getTemplate()
    {
        $params = array(null, null, 'admin', 'goodpassword', null, null);
        $response = new XoopsXmlRpcResponse();
        $module = new XoopsModule();
        $instance = new $this->myClass($params, $response, $module);

        $result = $instance->getTemplate();
        $msg = $response->render();
        $expected = '<?xml version="1.0"?>'
            . '<methodResponse><fault><value><struct>'
            . '<member><name>faultCode</name><value>107</value></member>'
            . "<member><name>faultString</name><value>Method not supported\n</value></member>"
            . '</struct></value></fault></methodResponse>';
        $this->assertSame($expected, $msg);
    }

    public function test_setTemplate()
    {
        $params = array(null, null, 'admin', 'goodpassword', null, null);
        $response = new XoopsXmlRpcResponse();
        $module = new XoopsModule();
        $instance = new $this->myClass($params, $response, $module);

        $result = $instance->setTemplate();
        $msg = $response->render();
        $expected = '<?xml version="1.0"?>'
            . '<methodResponse><fault><value><struct>'
            . '<member><name>faultCode</name><value>107</value></member>'
            . "<member><name>faultString</name><value>Method not supported\n</value></member>"
            . '</struct></value></fault></methodResponse>';
        $this->assertSame($expected, $msg);

        $params = array(null, null, 'admin', 'WRONG_password', null, null);
        $response = new XoopsXmlRpcResponse();
        $module = new XoopsModule();
        $instance = new $this->myClass($params, $response, $module);

        $result = $instance->setTemplate();
        $msg = $response->render();
        $expected = '<?xml version="1.0"?>'
            . '<methodResponse><fault><value><struct>'
            . '<member><name>faultCode</name><value>104</value></member>'
            . "<member><name>faultString</name><value>User authentication failed\n</value></member>"
            . '</struct></value></fault></methodResponse>';
        $this->assertSame($expected, $msg);
    }
}
