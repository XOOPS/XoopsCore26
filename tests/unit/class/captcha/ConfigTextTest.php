<?php
require_once(__DIR__.'/../../init_new.php');

global $config;
$config = null;

class ConfigTextTest extends \PHPUnit\Framework\TestCase
{
    public function test_100()
    {
        global $config;
        
        $xoops_root_path = \XoopsBaseConfig::get('root-path');
        require $xoops_root_path.'/class/captcha/config.text.php';
        $this->assertTrue(is_array($config));
        $this->assertTrue(isset($config['num_chars']));
    }
}
