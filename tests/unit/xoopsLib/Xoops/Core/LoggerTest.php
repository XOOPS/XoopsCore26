<?php
require_once(__DIR__.'/../../../init_new.php');

use Xoops\Core\Logger;
use Psr\Log\LogLevel;

class MY_Logger
{
    public function log($level, $message, array $context = array(), $echo = true)
    {
        $str = $level .':'. $message .':'. var_export($context,true);
        if ($echo)
            echo $str;
        else
            return $str;
    }

    public function quiet($echo = true)
    {
        $str = 'method Quiet called';
        if ($echo)
            echo $str;
        else
            return $str;
    }
}

class LoggerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Class
     */
    protected $object;

    protected $logger;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new Logger();
        $this->logger = new MY_Logger();
        $this->object->addLogger($this->logger);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

	public function test_getInstance()
	{
		$instance = Logger::getInstance();
		$this->assertInstanceOf('\Xoops\Core\Logger', $instance);

		$instance1 = Logger::getInstance();
		$this->assertSame($instance1, $instance);
	}

	public function test_handleError100()
	{
        $instance = $this->object;

        $errno = E_USER_NOTICE;
        $errstr = 'errstr';
        $errfile = 'errfile';
        $errline = 'errline';

        ob_start();
        $instance->handleError($errno,$errstr,$errfile,$errline);
        $result = ob_get_clean();

        $this->assertTrue(false !== strpos($result,'notice:'));
        $this->assertTrue(false !== strpos($result,'errstr'));
        $this->assertTrue(false !== strpos($result,'errfile'));
        $this->assertTrue(false !== strpos($result,'errline'));
	}

	public function test_handleError120()
	{
        $instance = $this->object;

        $errno = E_NOTICE;
        $errstr = 'errstr';
        $errfile = 'errfile';
        $errline = 'errline';

        ob_start();
        $instance->handleError($errno,$errstr,$errfile,$errline);
        $result = ob_get_clean();

        $this->assertTrue(false !== strpos($result,'notice:'));
        $this->assertTrue(false !== strpos($result,'errstr'));
        $this->assertTrue(false !== strpos($result,'errfile'));
        $this->assertTrue(false !== strpos($result,'errline'));
	}

	public function test_handleError140()
	{
        $instance = $this->object;

        $errno = E_WARNING;
        $errstr = 'errstr';
        $errfile = 'errfile';
        $errline = 'errline';

        ob_start();
        $instance->handleError($errno,$errstr,$errfile,$errline);
        $result = ob_get_clean();

        $this->assertTrue(false !== strpos($result,'warning:'));
        $this->assertTrue(false !== strpos($result,'errstr'));
        $this->assertTrue(false !== strpos($result,'errfile'));
        $this->assertTrue(false !== strpos($result,'errline'));
	}

	public function test_handleError160()
	{
        if (!(E_STRICT & error_reporting())) {
            $this->markTestSkipped('E_STRICT reporting is off');
        }
        $instance = $this->object;

        $errno = E_STRICT;
        $errstr = 'errstr';
        $errfile = 'errfile';
        $errline = 'errline';

        ob_start();
        $instance->handleError($errno,$errstr,$errfile,$errline);
        $result = ob_get_clean();

        $this->assertTrue(false !== strpos($result,'warning:'));
        $this->assertTrue(false !== strpos($result,'errstr'));
        $this->assertTrue(false !== strpos($result,'errfile'));
        $this->assertTrue(false !== strpos($result,'errline'));
	}

	public function test_handleError200()
	{
        $instance = $this->object;

        $errno = -1;
        $errstr = 'errstr';
        $errfile = 'errfile';
        $errline = 'errline';

        ob_start();
        $instance->handleError($errno,$errstr,$errfile,$errline);
        $result = ob_get_clean();

        $this->assertTrue(false !== strpos($result,'error:'));
        $this->assertTrue(false !== strpos($result,'errstr'));
        $this->assertTrue(false !== strpos($result,'errfile'));
        $this->assertTrue(false !== strpos($result,'errline'));
	}

	public function test_handleException()
	{
        $this->markTestIncomplete('to do');
	}

	public function test_sanitizePath()
	{
        $instance = $this->object;

        $path = '\\path\\';
        $result = $instance->sanitizePath($path);
        $this->assertSame('/path/', $result);

        $path = \XoopsBaseConfig::get('var-path');
        $result = $instance->sanitizePath($path);
        $this->assertSame('VAR', $result);

        $path = realpath(\XoopsBaseConfig::get('var-path'));
        $result = $instance->sanitizePath($path);
        $this->assertSame('VAR', $result);

        $path = \XoopsBaseConfig::get('lib-path');
        $result = $instance->sanitizePath($path);
        $this->assertSame('LIB', $result);

        $path = realpath(\XoopsBaseConfig::get('lib-path'));
        $result = $instance->sanitizePath($path);
        $this->assertSame('LIB', $result);

        $path = \XoopsBaseConfig::get('root-path');
        $result = $instance->sanitizePath($path);
        $this->assertSame('ROOT', $result);

        $path = realpath(\XoopsBaseConfig::get('root-path'));
        $result = $instance->sanitizePath($path);
        $this->assertSame('ROOT', $result);
	}

	public function test_emergency()
	{
        $instance = $this->object;

        $message = 'message';
        $context = array('k1'=>'v1', 'k2'=>'v2');
        ob_start();
        $instance->emergency($message,$context);
        $result = ob_get_clean();

        $expected = $this->logger->log(LogLevel::EMERGENCY,$message,$context,false);
        $this->assertSame($expected, $result);
	}

	public function test_alert()
	{
        $instance = $this->object;

        $message = 'message';
        $context = array('k1'=>'v1', 'k2'=>'v2');
        ob_start();
        $instance->alert($message,$context);
        $result = ob_get_clean();

        $expected = $this->logger->log(LogLevel::ALERT,$message,$context,false);
        $this->assertSame($expected, $result);
	}

	public function test_critical()
	{
        $instance = $this->object;

        $message = 'message';
        $context = array('k1'=>'v1', 'k2'=>'v2');
        ob_start();
        $instance->critical($message,$context);
        $result = ob_get_clean();

        $expected = $this->logger->log(LogLevel::CRITICAL,$message,$context,false);
        $this->assertSame($expected, $result);
	}

	public function test_error()
	{
        $instance = $this->object;

        $message = 'message';
        $context = array('k1'=>'v1', 'k2'=>'v2');
        ob_start();
        $instance->error($message,$context);
        $result = ob_get_clean();

        $expected = $this->logger->log(LogLevel::ERROR,$message,$context,false);
        $this->assertSame($expected, $result);
	}

	public function test_warning()
	{
        $instance = $this->object;

        $message = 'message';
        $context = array('k1'=>'v1', 'k2'=>'v2');
        ob_start();
        $instance->warning($message,$context);
        $result = ob_get_clean();

        $expected = $this->logger->log(LogLevel::WARNING,$message,$context,false);
        $this->assertSame($expected, $result);
	}

	public function test_notice()
	{
        $instance = $this->object;

        $message = 'message';
        $context = array('k1'=>'v1', 'k2'=>'v2');
        ob_start();
        $instance->notice($message,$context);
        $result = ob_get_clean();

        $expected = $this->logger->log(LogLevel::NOTICE,$message,$context,false);
        $this->assertSame($expected, $result);
	}

	public function test_info()
	{
        $instance = $this->object;

        $message = 'message';
        $context = array('k1'=>'v1', 'k2'=>'v2');
        ob_start();
        $instance->info($message,$context);
        $result = ob_get_clean();

        $expected = $this->logger->log(LogLevel::INFO,$message,$context,false);
        $this->assertSame($expected, $result);
	}

	public function test_debug()
	{
        $instance = $this->object;

        $message = 'message';
        $context = array('k1'=>'v1', 'k2'=>'v2');
        ob_start();
        $instance->debug($message,$context);
        $result = ob_get_clean();

        $expected = $this->logger->log(LogLevel::DEBUG,$message,$context,false);
        $this->assertSame($expected, $result);
	}

	public function test_quiet()
	{
        $instance = $this->object;

        ob_start();
        $instance->quiet();
        $result = ob_get_clean();

        $expected = $this->logger->quiet(false);
        $this->assertSame($expected, $result);
	}

	public function test___set()
	{
        if (! class_exists('', false)) {
            $path = \XoopsBaseConfig::get('root-path');
            XoopsLoad::addMap(array(
                'debugbarlogger' => $path . '/modules/debugbar/class/debugbarlogger.php',
            ));
        }

        $instance = $this->object;

        ob_start();
        $instance->activated = false;
        $result = ob_get_clean();

        $expected = $this->logger->quiet(false);
        $this->assertSame($expected, $result);
	}
/*
	public function test___get()
	{
        $instance = $this->object;

        $instance->dummy_var;
	}

	public function test___call()
	{
        $instance = $this->object;

        $instance->dummy_method();
	}
*/
}
