<?php
require_once(__DIR__.'/../../init_new.php');

global $config;
$config = null;

class ConfigImageTest extends \PHPUnit\Framework\TestCase
{
    public function test_100()
    {
        global $config;

        $xoops_root_path = \XoopsBaseConfig::get('root-path');
        require $xoops_root_path.'/class/captcha/config.image.php';
        $this->assertTrue(is_array($config));
        $this->assertTrue(isset($config['num_chars']));
        $this->assertTrue(isset($config['casesensitive']));
        $this->assertTrue(isset($config['fontsize_min']));
        $this->assertTrue(isset($config['fontsize_max']));
        $this->assertTrue(isset($config['background_type']));
        $this->assertTrue(isset($config['background_num']));
        $this->assertTrue(isset($config['polygon_point']));
        $this->assertTrue(isset($config['skip_characters']));
    }
}
