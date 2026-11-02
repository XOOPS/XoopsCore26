<?php
require_once(__DIR__.'/../../../init_new.php');

use Xoops\Core\Security;

class SecurityTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Class
     */
    protected $object;

    protected $savedServer;
    protected $savedSession;
    protected $savedPost;
    protected $savedModuleConfig;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new Security();
        $this->savedServer = $_SERVER;
        $this->savedSession = $_SESSION;
        $this->savedPost = $_POST;
        $this->savedModuleConfig = \Xoops::getInstance()->moduleConfig;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        $_SERVER = $this->savedServer;
        $_SESSION = $this->savedSession;
        $_POST = $this->savedPost;
        \Xoops::getInstance()->moduleConfig = $this->savedModuleConfig;
    }

    public function test___construct()
    {
        $instance = $this->object;
        $this->assertInstanceOf('\Xoops\Core\Security', $instance);
    }

    public function test_check()
    {
        $instance = $this->object;

        if(isset($_SESSION)) unset($_SESSION['XOOPS_TOKEN_SESSION']);
        $token = $instance->createToken();
        $this->assertTrue(!empty($token));

        $value = $instance->check(true, $token);
        $this->assertTrue($value);
        $this->assertTrue(empty($_SESSION['XOOPS_TOKEN_SESSION']));

        $value = $instance->check(true, false);
        $this->assertFalse($value);
        $err = $instance->getErrors();
        $this->assertTrue(is_array($err));

        $value = $instance->check(true, $token);
        $this->assertFalse($value);
        $err = $instance->getErrors();
        $this->assertTrue(is_array($err));

        unset($_SESSION['XOOPS_TOKEN_SESSION']);
        $token = $instance->createToken(1);
        $this->assertTrue(!empty($token));
        sleep(2);
        $value = $instance->check(true, $token);
        $this->assertFalse($value);
        $err = $instance->getErrors();
        $this->assertTrue(is_array($err));
        $this->assertTrue(empty($_SESSION['XOOPS_TOKEN_SESSION']));
    }

    // a wrongful exit() here would end the whole runner with status 0
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function test_requireAjaxToken_keepsTokenForNextToggle()
    {
        unset($_SESSION['XOOPS_TOKEN_SESSION']);
        $token = $this->object->createToken(3600);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['XOOPS_TOKEN_REQUEST'] = $token;

        // returns (does not exit) for a valid POST, twice with one token
        $this->object->requireAjaxToken();
        $this->object->requireAjaxToken();
        $this->assertSame($token, $_SESSION['XOOPS_TOKEN_SESSION'][0]['id']);
    }

    public static function rejectedAjaxRequests(): array
    {
        return [
            'GET with body token' => ['GET', 'valid', false],
            'POST without token'  => ['POST', null, false],
            'POST invalid token'  => ['POST', 'nope', false],
            'POST expired token'  => ['POST', 'valid', true],
            'POST token in query' => ['POST', 'query', false],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('rejectedAjaxRequests')]
    public function test_checkAjaxToken_rejects(string $method, ?string $token, bool $expired)
    {
        unset($_SESSION['XOOPS_TOKEN_SESSION']);
        $valid = $this->object->createToken(3600);
        if ($expired) {
            $_SESSION['XOOPS_TOKEN_SESSION'][0]['expire'] = time() - 1;
        }
        $_SERVER['REQUEST_METHOD'] = $method;
        $savedGet = $_GET;
        $savedPost = $_POST;
        $savedRequest = $_REQUEST;
        unset($_POST['XOOPS_TOKEN_REQUEST'], $_REQUEST['XOOPS_TOKEN_REQUEST']);
        if ('query' === $token) {
            $_GET['XOOPS_TOKEN_REQUEST'] = $valid;
            $_REQUEST['XOOPS_TOKEN_REQUEST'] = $valid;
        } elseif (null !== $token) {
            $_POST['XOOPS_TOKEN_REQUEST'] = 'valid' === $token ? $valid : $token;
        }

        try {
            $this->assertFalse($this->object->checkAjaxToken());
        } finally {
            $_GET = $savedGet;
            $_POST = $savedPost;
            $_REQUEST = $savedRequest;
        }
    }

    public function test_checkAjaxToken_acceptsValidPost()
    {
        unset($_SESSION['XOOPS_TOKEN_SESSION']);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['XOOPS_TOKEN_REQUEST'] = $this->object->createToken(3600);
        $this->assertTrue($this->object->checkAjaxToken());
    }

    public function test_createToken()
    {
        $instance = $this->object;

        $value = $instance->createToken();
        $x = $_SESSION['XOOPS_TOKEN_SESSION'];
        $token = array_pop($x);
        $this->assertFalse(is_null($token));
        $id = $token['id'];
        $expire = $token['expire'];
        $db_prefix = \XoopsBaseConfig::get('db-prefix');
        $this->assertSame($id, $value);
        unset($_SESSION['XOOPS_TOKEN_SESSION']);

        $tkName = 'MY_TOKEN';
        $value = $instance->createToken(1, $tkName);
        $x = $_SESSION[$tkName.'_SESSION'];
        $token = array_pop($x);
        $this->assertFalse(is_null($token));
        $id = $token['id'];
        $this->assertSame($id, $value);
        unset($_SESSION['MY_TOKEN_SESSION']);
    }

    public function test_validateToken()
    {
        $instance = $this->object;

        unset($_SESSION['XOOPS_TOKEN_SESSION']);
        $token = $instance->createToken();
        $this->assertTrue(!empty($token));

        $value = $instance->validateToken($token);
        $this->assertTrue($value);
        $this->assertTrue(empty($_SESSION['XOOPS_TOKEN_SESSION']));

        $value = $instance->validateToken(false);
        $this->assertFalse($value);
        $err = $instance->getErrors();
        $this->assertTrue(is_array($err));

        $value = $instance->validateToken($token);
        $this->assertFalse($value);
        $err = $instance->getErrors();
        $this->assertTrue(is_array($err));

        unset($_SESSION['XOOPS_TOKEN_SESSION']);
        $token = $instance->createToken(1);
        $this->assertTrue(!empty($token));
        sleep(2);
        $value = $instance->validateToken($token);
        $this->assertFalse($value);
        $err = $instance->getErrors();
        $this->assertTrue(is_array($err));
        $this->assertTrue(empty($_SESSION['XOOPS_TOKEN_SESSION']));
    }

    public function test_clearTokens()
    {
        $instance = $this->object;

        unset($_SESSION['XOOPS_TOKEN_SESSION']);
        $token = $instance->createToken();
        $this->assertTrue(!empty($token));

        $instance->clearTokens();
        $this->assertTrue(empty($_SESSION['XOOPS_TOKEN_SESSION']));
    }

    public function test_garbageCollection()
    {
        $instance = $this->object;

        unset($_SESSION['XOOPS_TOKEN_SESSION']);
        $token1 = $instance->createToken(1);
        $this->assertTrue(!empty($token1));

        $token2 = $instance->createToken(10);
        $this->assertTrue(!empty($token2));

        $this->assertTrue(count($_SESSION['XOOPS_TOKEN_SESSION']) == 2);

        sleep(2);

        $instance->garbageCollection();
        $this->assertTrue(count($_SESSION['XOOPS_TOKEN_SESSION']) == 1);
    }

    public function test_checkReferer()
    {
        $instance = $this->object;

        $value = $instance->checkReferer(0);
        $this->assertTrue($value);

        $_SERVER['HTTP_REFERER'] = \XoopsBaseConfig::get('url');;
        $value = $instance->checkReferer();
        $this->assertTrue($value);

        $_SERVER['HTTP_REFERER'] = 'dummy';
        $value = $instance->checkReferer();
        $this->assertFalse($value);

        $_SERVER['HTTP_REFERER'] = XOOPS_URL;
        $value = $instance->checkReferer();
        $this->assertTrue($value);
    }

    public function test_checkBadips()
    {
        $instance = $this->object;

        unset($_SERVER['REMOTE_ADDR']);
        $result = $instance->checkBadips();
        $this->assertNull($result);

        $xoops = \Xoops::getInstance();
        $xoops->setConfig('enable_badips',1);
        $xoops->setConfig('bad_ips', array('bad_ip1', 'bad_ip2'));

        $_SERVER['REMOTE_ADDR'] = 'bad_ip3';
        $result = $instance->checkBadips();
        $this->assertNull($result);
    }

    public function test_getTokenHTML()
    {
        $instance = $this->object;

        $value = $instance->getTokenHTML();
        $this->assertTrue(strpos($value, '<input') === 0);
        $this->assertNotFalse(strpos($value, 'type="hidden"'));
        $this->assertNotFalse(strpos($value, 'name="XOOPS_TOKEN_REQUEST"'));
        $this->assertNotFalse(strpos($value, 'id="XOOPS_TOKEN_REQUEST"'));
        $this->assertNotFalse(strpos($value, 'value="'));

        $token = "MY_TOKEN";
        $value = $instance->getTokenHTML($token);
        $this->assertTrue(strpos($value, '<input') === 0);
        $this->assertNotFalse(strpos($value, 'type="hidden"'));
        $this->assertNotFalse(strpos($value, 'name="'.$token.'_REQUEST"'));
        $this->assertNotFalse(strpos($value, 'id="'.$token.'_REQUEST"'));
        $this->assertNotFalse(strpos($value, 'value="'));
    }

    public function test_getErrors()
    {
        $instance = $this->object;

        $str1 = "string1";
        $instance->setErrors($str1);
        $instance->setErrors($str1);
        $value = $instance->getErrors();
        $this->assertTrue(is_array($value));
        $this->assertTrue(count($value)==2);
        $value = $instance->getErrors(true);
        $this->assertTrue(is_string($value));
        $this->assertSame($str1.'<br />'.$str1.'<br />', $value);
    }

}
