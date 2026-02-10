<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

use Xoops\Core\HttpRequest;
use Xmf\Request;
use Xoops\Core\FixedGroups;
use Xoops\Core\Handler\Factory as HandlerFactory;
use Xoops\Core\Kernel\Handlers\XoopsModule;
use Xoops\Core\Kernel\Handlers\XoopsUser;
use Xoops\Core\Theme\XoopsTheme;
use Xoops\Core\XoopsTpl;
use Psr\Log\LogLevel;

/**
 * XOOPS
 *
 * @category  Xoops
 * @package   Xoops
 * @author    trabis <lusopoemas@gmail.com>
 * @author    formuss
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2011-2015 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      http://xoops.org
 */
class Xoops
{
    const VERSION = 'XOOPS 2.6.0-Alpha 3';

    /**
     * @var null|Xoops\Core\Session\Manager
     */
    public $sessionManager = null;

    /**
     * @var null|XoopsModule
     */
    public $module = null;

    /**
     * @var array
     */
    public $config = array();

    /**
     * @var array
     */
    public $moduleConfig = array();

    /**
     * @var array
     */
    public $moduleDirname = '';

    /**
     * @var XoopsUser|string
     */
    public $user = '';

    /**
     * @var bool
     */
    public $userIsAdmin = false;

    /**
     * @var array
     */
    public $option = array();

    /**
     * @var XoopsTpl|null
     */
    private $tpl = null;

    /**
     * @var XoopsTheme|null
     */
    private $theme = null;

    /**
     * @var array
     */
    public $paths = array(
        'XOOPS'  => array(), 'www' => array(), 'var' => array(), 'lib' => array(), 'modules' => array(),
        'themes' => array(), 'media' => array()
    );

    /**
     * @var string
     */
    public $tpl_name = '';

    /**
     * @var HandlerFactory
     */
    private $handlerFactory;

    /**
     * @var array
     */
    private $kernelHandlers = array();

    /**
     * @var array
     */
    private $moduleHandlers = array();

    /**
     * @var null|array
     */
    private $activeModules = null;

    /**
     * @var array
     */
    private $moduleConfigs = array();

    /**
     * @var bool
     */
    public $isAdminSide = false;

    /**
     * Actual Xoops OS
     */
    private function __construct()
    {
        $root = \XoopsBaseConfig::get('root-path');
        $lib = \XoopsBaseConfig::get('lib-path');
        $var = \XoopsBaseConfig::get('var-path');

        $url = \XoopsBaseConfig::get('url');

        $this->paths['www'] = array($root, $url);
        $this->paths['var'] = array($var, null);
        $this->paths['lib'] = array($lib, $url . '/browse.php');
        $this->paths['XOOPS'] = array($lib, $url . '/browse.php');
        $this->paths['assets'] = array(\XoopsBaseConfig::get('asset-path'), \XoopsBaseConfig::get('asset-url'));
        $this->paths['images'] = array($root . '/images', $url . '/images');
        $this->paths['language'] = array($root . '/language', $url . '/language');
        $this->paths['locale'] = array($root . '/locale', $url . '/locale');
        $this->paths['media'] = array(\XoopsBaseConfig::get('media-path'), \XoopsBaseConfig::get('media-url'));
        $this->paths['modules'] = array($root . '/modules', $url . '/modules');
        $this->paths['themes'] = array(\XoopsBaseConfig::get('themes-path'), \XoopsBaseConfig::get('themes-url'));
        $this->paths['uploads'] = array(\XoopsBaseConfig::get('uploads-path'), \XoopsBaseConfig::get('uploads-url'));

        $this->pathTranslation();
    }

    /**
     * Access the only instance of this class
     *
     * @return Xoops
     */
    public static function getInstance()
    {
        static $instance;
        if (!isset($instance)) {
            $class = __CLASS__;
            $instance = new $class();
        }
        return $instance;
    }

    /**
     * get database connection instance
     *
     * @return Xoops\Core\Database\Connection
     */
    public function db()
    {
        return \Xoops\Core\Database\Factory::getConnection();
    }

    /**
     * get a \Xoops\Core\Cache\Access object for a named cache
     *
     * @param string $cacheName a named cached pool
     *
     * @return \Xoops\Core\Cache\Access
     */
    public function cache($cacheName = 'default')
    {
        static $cacheManager;

        if (!isset($cacheManager)) {
            $cacheManager = new \Xoops\Core\Cache\CacheManager();
        }

        return $cacheManager->getCache($cacheName);
    }

    /**
     * get the system logger instance
     *
     * @return \Xoops\Core\Logger
     */
    public function logger()
    {
        return \Xoops\Core\Logger::getInstance();
    }


    /**
     * get the event processor
     *
     * @return \Xoops\Core\Events instance
     */
    public function events()
    {
        return \Xoops\Core\Events::getInstance();
    }

    /**
     * get the asset utility
     *
     * @return Xoops\Core\Assets instance
     */
    public function assets()
    {
        static $instance;
        if (!isset($instance)) {
            $instance = new \Xoops\Core\Assets;
        }
        return $instance;
    }

    /**
     * get the service manager
     *
     * @param string $service - service name
     *
     * @return Xoops\Core\Service\Provider instance
     */
    public function service($service)
    {
        static $instance;
        if (!isset($instance)) {
            $instance = \Xoops\Core\Service\Manager::getInstance();
        }
        return $instance->locate($service);
    }

    /**
     * provide a common registry instance
     *
     * @return Xoops\Core\Registry
     */
    public function registry()
    {
        static $instance;
        if (!isset($instance)) {
            $instance = new \Xoops\Core\Registry();
        }
        return $instance;
    }

    /**
     * get security instance
     *
     * @return XoopsSecurity
     */
    public function security()
    {
        static $instance;
        if (!isset($instance)) {
            $instance = new \Xoops\Core\Security();
        }
        return $instance;
    }

    /**
     * get current template engine
     *
     * @return null|XoopsTpl
     */
    public function tpl()
    {
        return $this->tpl;
    }

    /**
     * set current template engine
     *
     * @param XoopsTpl $tpl template engine
     *
     * @return XoopsTpl
     */
    public function setTpl(XoopsTpl $tpl)
    {
        return $this->tpl = $tpl;
    }

    /**
     * establish the theme
     *
     * @param null|string $tpl_name base template
     *
     * @return null|XoopsTheme
     */
    public function theme($tpl_name = null)
    {
        if (!isset($this->theme)) {
            if ($tpl_name) {
                $tpl_info = $this->getTplInfo($tpl_name);
                $this->tpl_name = $tpl_info['tpl_name'];
            } else {
                $tpl_name = 'module:system/system_dummy.tpl';
                $tpl_info = $this->getTplInfo($tpl_name);
                $this->tpl_name = $tpl_info['tpl_name'];
            }
            if (!$this->isAdminSide) {
                $xoopsThemeFactory = new \Xoops\Core\Theme\Factory();
                $xoopsThemeFactory->allowedThemes = $this->getConfig('theme_set_allowed');
                $xoopsThemeFactory->defaultTheme = $this->getConfig('theme_set');
                $this->setTheme($xoopsThemeFactory->createInstance(array('contentTemplate' => $this->tpl_name)));
            } else {
                $adminThemeFactory = new \Xoops\Core\Theme\AdminFactory();
                $this->setTheme($adminThemeFactory->createInstance(array(
                    'folderName'      => 'default', 'themesPath' => 'modules/system/themes',
                    'contentTemplate' => $this->tpl_name
                )));
                //$this->theme()->loadLocalization('admin');
                list($cssAssets, $jsAssets) = $this->theme()->getLocalizationAssets('admin');
                if (!empty($cssAssets)) {
                    $this->theme()->addBaseStylesheetAssets($cssAssets);
                }
                if (!empty($jsAssets)) {
                    $this->theme()->addBaseScriptAssets($jsAssets);
                }
            }
        } else {
            if ($tpl_name) {
                $tpl_info = $this->getTplInfo($tpl_name);
                $this->tpl_name = $tpl_info['tpl_name'];
                $this->theme->contentTemplate = $this->tpl_name;
            }
        }
        $GLOBALS['xoTheme'] = $this->theme;
        return $this->theme;
    }

    /**
     * set theme
     *
     * @param XoopsTheme $theme theme
     *
     * @return XoopsTheme
     */
    public function setTheme(XoopsTheme $theme)
    {
        return $this->theme = $theme;
    }

    /**
     * Convert a XOOPS path to a physical one
     *
     * @param string $url     url to derive path from
     * @param bool   $virtual virtual
     *
     * @return string
     */
    public function path($url, $virtual = false)
    {
        $url = $this->normalizePath($url);
        $rootPath = $this->normalizePath(\XoopsBaseConfig::get('root-path') . '/');
        if (0 === strpos($url, $rootPath)) {
            $url = substr($url, strlen($rootPath));
        }
        //$url = ltrim($url, '/');
        $parts = explode('/', $url, 2);
        $root = isset($parts[0]) ? $parts[0] : '';
        $path = isset($parts[1]) ? $parts[1] : '';
        if (!isset($this->paths[$root])) {
            list($root, $path) = array('www', $url);
        }
        if (!$virtual) { // Returns a physical path
            $path = $this->paths[$root][0] . '/' . $path;
            //$path = str_replace('/', DIRECTORY_SEPARATOR, $path);
            return $path;
        }
        return !isset($this->paths[$root][1]) ? '' : ($this->paths[$root][1] . '/' . $path);
    }

    /**
     * Convert path separators to unix style
     *
     * @param string $path path to normalize
     *
     * @return string normalized path
     */
    public function normalizePath($path)
    {
        return str_replace('\\', '/', $path);
    }

    /**
     * Convert a XOOPS path to an URL
     *
     * @param string $url path (or url)
     *
     * @return string
     */
    public function url($url)
    {
        return (false !== strpos($url, '://') ? $url : $this->path($url, true));
    }

    /**
     * Build an URL with the specified request params
     *
     * @param string $url    base url
     * @param array  $params parameters to add to the url
     *
     * @return string
     */
    public function buildUrl($url, $params = array())
    {
        if ($url === '.') {
            $url = $_SERVER['REQUEST_URI'];
        }
        $split = explode('?', $url);
        if (count($split) > 1) {
            list($url, $query) = $split;
            parse_str($query, $query);
            $params = array_merge($query, $params);
        }
        if (!empty($params)) {
            foreach ($params as $k => $v) {
                $params[$k] = $k . '=' . rawurlencode($v);
            }
            $url .= '?' . implode('&', $params);
        }
        return $url;
    }

    /**
     * Check if a path exists
     *
     * @param string $path       filesystem path
     * @param string $error_type error level i.e. Psr\Log\LogLevel
     *
     * @return string|false
     */
    public function pathExists($path, $error_type)
    {
        if (XoopsLoad::fileExists($path)) {
            return $path;
        } else {
            $this->logger()->log(
                LogLevel::WARNING,
                \XoopsLocale::E_FILE_NOT_FOUND,
                array($path, $error_type)
            );

            //trigger_error(XoopsLocale::E_FILE_NOT_FOUND, $error_type);
            return false;
        }
    }

    /**
     * Start gzipCompression output buffer
     *
     * @return void
     */
    public function gzipCompression()
    {
        /**
         * Disable gzip compression if PHP is run under CLI mode and needs refactored to work correctly
         */
        if (empty($_SERVER['SERVER_NAME']) || substr(PHP_SAPI, 0, 3) === 'cli') {
            $this->setConfig('gzip_compression', 0);
        }

        if ($this->getConfig('gzip_compression') == 1
            && extension_loaded('zlib')
            && !ini_get('zlib.output_compression')
        ) {
            if (@ini_get('zlib.output_compression_level') < 0) {
                ini_set('zlib.output_compression_level', 6);
            }
            ob_start('ob_gzhandler');
        }
    }

    /**
     * Translate a path
     *
     * @return void
     */
    public function pathTranslation()
    {
        /**
         * *#@+
         * Host abstraction layer
         */
        if (!isset($_SERVER['PATH_TRANSLATED']) && isset($_SERVER['SCRIPT_FILENAME'])) {
            $_SERVER['PATH_TRANSLATED'] = $_SERVER['SCRIPT_FILENAME']; // For Apache CGI
        } else {
            if (isset($_SERVER['PATH_TRANSLATED']) && !isset($_SERVER['SCRIPT_FILENAME'])) {
                $_SERVER['SCRIPT_FILENAME'] = $_SERVER['PATH_TRANSLATED']; // For IIS/2K now I think :-(
            }
        }
        /**
         * User Mulitbytes
         */
        if (empty($_SERVER['REQUEST_URI'])) { // Not defined by IIS
            // Under some configs, IIS makes SCRIPT_NAME point to php.exe :-(
            if (!($_SERVER['REQUEST_URI'] = @$_SERVER['PHP_SELF'])) {
                $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
            }
            if (isset($_SERVER['QUERY_STRING'])) {
                $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
            }
        }
    }

    /**
     * Select Theme
     *
     * @return void
     */
    public function themeSelect()
    {
        $xoopsThemeSelect = Request::getString('xoops_theme_select', '', 'POST');
        $allowedThemes = $this->getConfig('theme_set_allowed');
        if (!is_array($allowedThemes)) {
            $allowedThemes = empty($allowedThemes) ? [] : (array) $allowedThemes;
        }
        if (!empty($xoopsThemeSelect) && in_array($xoopsThemeSelect, $allowedThemes)) {
            $this->setConfig('theme_set', $xoopsThemeSelect);
            $_SESSION['xoopsUserTheme'] = $xoopsThemeSelect;
        } else {
            if (!empty($_SESSION['xoopsUserTheme'])
                && in_array($_SESSION['xoopsUserTheme'], $allowedThemes)
            ) {
                $this->setConfig('theme_set', $_SESSION['xoopsUserTheme']);
            }
        }
    }

    /**
     * Gets module, type and file from a tpl name
     *
     * @param string $tpl_name in form type:module/filename.tpl
     *
     * @return array|false associative array of 'tpl_name', 'type', 'module', 'file'
     *                     or false on error
     */
    public function getTplInfo($tpl_name)
    {
        $parts = array();
        $matched = preg_match('#(\w+):(\w+)/(.*)$#', $tpl_name, $parts);
        if ($matched) {
            $names = array('tpl_name', 'type', 'module', 'file');
            $ret = array();
            for ($i=0; $i<4; ++$i) {
                $ret[$names[$i]] = $parts[$i];
            }
        } else {
            // this should be eliminated
            $this->events()->triggerEvent('debug.log', "Sloppy template: " . $tpl_name);
            $ret = array();
            $ret['type'] = $this->isAdminSide ? 'admin' : 'module';
            $info = explode(':', $tpl_name);
            if (count($info) == 2) {
                $ret['type'] = $info[0];
                $tpl_name = str_replace($ret['type'] . ':', '', $tpl_name);
            }

            if ($ret['type'] === 'db') {
                //For legacy compatibility
                $ret['type'] = $this->isAdminSide ? 'admin' : 'module';
            }

            $info = explode('|', $tpl_name);
            if (count($info) == 2) {
                $ret['module'] = $info[0];
                $ret['file'] = $info[1];
            } else {
                $ret['module'] = 'system';
                $ret['file'] = $tpl_name;
                if ($this->isModule()) {
                    $ret['module'] = $this->module->getVar('dirname', 'n');
                }
            }
            $ret['tpl_name'] = $ret['type'] . ':' . $ret['module'] . '/' . $ret['file'];
        }

        return $ret;
    }

    /**
     * Render Header
     *
     * @param string|null $tpl_name template name
     *
     * @return null|boolean
     */
    public function header($tpl_name = null)
    {
        static $included = false;
        if ($included) {
            return false;
        }
        $included = true;

        $this->events()->triggerEvent('core.header.start');

        //For legacy
        if (!$tpl_name && isset($this->option['template_main'])) {
            $tpl_name = $this->option['template_main'];
            $this->deprecated(
                'XoopsOption \'template_main\' is deprecated, please use $xoops->header(\'templatename.tpl\') instead'
            );
        }
        $this->theme($tpl_name);
        $this->tpl()->assign('xoops', $this);

        if ($this->isAdminSide) {
            $this->events()->triggerEvent('system.class.gui.header');
            include_once $this->path('modules/system/themes/default/default.php');
            $gui = new XoopsGuiDefault();
            $gui->header();
        } else {
            $this->events()->triggerEvent('core.header.addmeta');
            // Temporary solution for start page redirection
            if (defined("XOOPS_STARTPAGE_REDIRECTED")) {
                $smarty = $repeat = null;
                $this->theme()->headContent(
                    null,
                    "<base href='" . \XoopsBaseConfig::get('url') . '/modules/'
                    . $this->getConfig('startpage') . "/' />",
                    $smarty,
                    $repeat
                );
            }

            // Sets cache time
            if ($this->isModule()) {
                $cache_times = $this->getConfig('module_cache');
                $this->theme()->contentCacheLifetime =
                    isset($cache_times[$this->module->getVar('mid')]) ? $cache_times[$this->module->getVar('mid')] : 0;
                // Tricky solution for setting cache time for homepage
            } else {
                if ($this->tpl_name === 'module:system/system_homepage.tpl') {
                    // $this->theme->contentCacheLifetime = 604800;
                }
            }
            $this->events()->triggerEvent('core.header.checkcache');
            if ($this->theme()->checkCache()) {
                exit();
            }
        }

        if (!isset($this->tpl_name) && $this->isModule()) {
            ob_start();
        }

        $this->events()->triggerEvent('core.header.end');
        return true;
    }

    /**
     * Render Footer
     *
     * @return false|null
     */
    public function footer()
    {
        static $included = false;
        if ($included) {
            return false;
        }
        $included = true;

        $this->events()->triggerEvent('core.footer.start');

        if (!headers_sent()) {
            header('Content-Type:text/html; charset=' . XoopsLocale::getCharset());
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header('Cache-Control: private, no-cache');
            header('Pragma: no-cache');
        }

        if (isset($this->option['template_main'])
            && $this->option['template_main'] != $this->theme()->contentTemplate
        ) {
            trigger_error("xoopsOption[template_main] should be defined before including header.php", E_USER_WARNING);
            $this->theme()->contentTemplate = $this->tpl_name;
        }
        $this->theme()->render();
        $this->events()->triggerEvent('core.footer.end');
        exit();
    }

    /**
     * Check if a module is set
     *
     * @return bool
     */
    public function isModule()
    {
        return $this->module instanceof XoopsModule ? true : false;
    }

    /**
     * Check if a user is set
     *
     * @return bool
     */
    public function isUser()
    {
        return $this->user instanceof XoopsUser ? true : false;
    }

    /**
     * Check if user is admin
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->userIsAdmin;
    }

    /**
     * Get handler of Block
     *
     * @param boolean $optional true if failure to load handler should be considered a warning, not an error
     *
     * @return \Xoops\Core\Kernel\Handlers\XoopsBlockHandler
     */
    public function getHandlerBlock($optional = false)
    {
        return $this->getHandler('Block', $optional);
    }

    /**
     * Get handler of Block Module Link
     *
     * @param boolean $optional true if failure to load handler should be considered a warning, not an error
     *
     * @return \Xoops\Core\Kernel\Handlers\XoopsBlockModuleLinkHandler
     */
    public function getHandlerBlockModuleLink($optional = false)
    {
        return $this->getHandler('BlockModuleLink', $optional);
    }

    /**
     * Get handler of Config
     *
     * @param boolean $optional true if failure to load handler should be considered a warning, not an error
     *
     * @return \Xoops\Core\Kernel\Handlers\XoopsConfigHandler
     */
    public function getHandlerConfig($optional = false)
    {
        return $this->getHandler('Config', $optional);
    }

    /**
     * Get handler of Config  Item
     *
     * @param boolean $optional true if failure to load handler should be considered a warning, not an error
     *
     * @return \Xoops\Core\Kernel\Handlers\XoopsConfigItemHandler
     */
    public function getHandlerConfigItem($optional = false)
    {
        return $this->getHandler('ConfigItem', $optional);
    }

    /**
     * Get handler of Config Option
     *
     * @param boolean $optional true if failure to load handler should be considered a warning, not an error
     *
     * @return \Xoops\Core\Kernel\Handlers\XoopsConfigOptionHandler
     */
    public function getHandlerConfigOption($optional = false)
    {
        return $this->getHandler('ConfigOption', $optional);
    }

    /**
     * Get handler of Group
     *
     * @param boolean $optional true if failure to load handler should be considered a warning, not an error
     *
     * @return \Xoops\Core\Kernel\Handlers\XoopsGroupHandler
     */
    public function getHandlerGroup($optional = false)
    {
        return $this->getHandler('Group', $optional);
    }

    /**
     * Get handler of Group Permission
     *
     * @param boolean $optional true if failure to load handler should be considered a warning, not an error
     *
     * @return \Xoops\Core\Kernel\Handlers\XoopsGroupPermHandler
     */
    public function getHandlerGroupPermission($optional = false)
    {
        return $this->getHandler('GroupPerm', $optional);
    }

    /**
     * Get handler of Member
     *
     * @param boolean $optional true if failure to load handler should be considered a warning, not an error
     *
     * @return \Xoops\Core\Kernel\Handlers\XoopsMemberHandler
     */
    public function getHandlerMember($optional = false)
    {
        return $this->getHandler('Member', $optional);
    }

    /**
     * Get handler of Membership
     *
     * @param boolean $optional true if failure to load handler should be considered a warning, not an error
     *
     * @return \Xoops\Core\Kernel\Handlers\XoopsMembershipHandler
     */
    public function getHandlerMembership($optional = false)
    {
        return $this->getHandler('Membership', $optional);
    }

    /**
     * Get handler of Module
     *
     * @param boolean $optional true if failure to load handler should be considered a warning, not an error
     *
     * @return \Xoops\Core\Kernel\Handlers\XoopsModuleHandler
     */
    public function getHandlerModule($optional = false)
    {
        return $this->getHandler('Module', $optional);
    }

    /**
     * Get handler of Online
     *
     * @param boolean $optional true if failure to load handler should be considered a warning, not an error
     *
     * @return \Xoops\Core\Kernel\Handlers\XoopsOnlineHandler
     */
    public function getHandlerOnline($optional = false)
    {
        return $this->getHandler('Online', $optional);
    }

    /**
     * Get handler of Private Message
     *
     * @param boolean $optional true if failure to load handler should be considered a warning, not an error
     *
     * @return \Xoops\Core\Kernel\Handlers\XoopsPrivateMessageHandler
     */
    public function getHandlerPrivateMessage($optional = false)
    {
        return $this->getHandler('Privmessage', $optional);
    }

    /**
     * Get the session manager
     *
     * @return Xoops\Core\Session\Manager
     */
    public function session()
    {
        if ($this->sessionManager === null) {
            $this->sessionManager = new \Xoops\Core\Session\Manager();
        }
        return $this->sessionManager;
    }

    /**
     * Get handler of Template File
     *
     * @param boolean $optional true if failure to load handler should be considered a warning, not an error
     *
     * @return \Xoops\Core\Kernel\Handlers\XoopsTplFileHandler
     */
    public function getHandlerTplFile($optional = false)
    {
        return $this->getHandler('tplfile', $optional);
    }

    /**
     * Get handler of Template Set
     *
     * @param boolean $optional true if failure to load handler should be considered a warning, not an error
     *
     * @return \Xoops\Core\Kernel\Handlers\XoopsTplSetHandler
     */
    public function getHandlerTplSet($optional = false)
    {
        return $this->getHandler('Tplset', $optional);
    }

    /**
     * Get handler of User
     *
     * @param boolean $optional true if failure to load handler should be considered a warning, not an error
     *
     * @return \Xoops\Core\Kernel\Handlers\XoopsUserHandler
     */
    public function getHandlerUser($optional = false)
    {
        return $this->getHandler('user', $optional);
    }

    /**
     * Get handler
     *
     * @param string  $name     name of handler
     * @param boolean $optional true if failure to load handler should be considered a warning, not an error
     *
     * @return XoopsObjectHandler|XoopsPersistableObjectHandler|null
     */
    protected function getHandler($name, $optional = false)
    {
        if (!isset($this->kernelHandlers[$name])) {
            if (!isset($this->handlerFactory)) {
                $this->handlerFactory = HandlerFactory::getInstance();
            }
            $handler = $this->handlerFactory->newSpec()->scheme('kernel')->name($name)->optional($optional)->build();
            if ($handler === null) {
                $this->logger()->log(
                    \Psr\Log\LogLevel::WARNING,
                    sprintf('A handler for %s is not available', $name)
                );
            }
            $this->kernelHandlers[$name] = $handler;
        }

        return $this->kernelHandlers[$name];
    }

    /**
     * Get Module Handler
     *
     * @param string|null $name       name of handler
     * @param string|null $module_dir dirname of module
     * @param boolean     $optional   true if failure to load handler should be considered a warning, not an error
     *
     * @return XoopsObjectHandler|XoopsPersistableObjectHandler|bool
     */
    public function getModuleHandler($name = null, $module_dir = null, $optional = false)
    {
        // if $module_dir is not specified
        if (!isset($module_dir)) {
            // if a module is loaded
            if ($this->module instanceof XoopsModule) {
                $module_dir = $this->module->getVar('dirname', 'n');
            } else {
                trigger_error('No Module is loaded', E_USER_ERROR);
            }
        } else {
            $module_dir = trim($module_dir);
        }
        $name = (!isset($name)) ? $module_dir : trim($name);
        if (!isset($this->moduleHandlers[$module_dir][$name])) {
            if (!isset($this->handlerFactory)) {
                $this->handlerFactory = HandlerFactory::getInstance();
            }
            $handler = $this->handlerFactory->create($name, $module_dir, $optional);
            if ($handler === null) {
                $this->logger()->log(
                    LogLevel::WARNING,
                    sprintf('No handler for %s exists in module %s', $name, $module_dir)
                );
            }
            $this->moduleHandlers[$module_dir][$name] = $handler;
        }
        return $this->moduleHandlers[$module_dir][$name];
    }

    /**
     * Get Module Form
     *
     * @param XoopsObject $obj        object to populate form
     * @param string      $name       name of form
     * @param string      $module_dir dirname of associated module
     *
     * @return Xoops\Form\Form|bool
     */
    public function getModuleForm($obj, $name, $module_dir = null)
    {
        if (empty($name)) {
            return false;
        }
        if (empty($module_dir)) {
            if ($this->isModule()) {
                $module_dir = $this->module->getVar('dirname', 'n');
            } else {
                return false;
            }
        }
        if (XoopsLoad::fileExists(
            $hnd_file = \XoopsBaseConfig::get('root-path') . "/modules/{$module_dir}/class/form/{$name}.php"
        )) {
            include_once $hnd_file;
            $class = ucfirst(strtolower($module_dir)) . ucfirst($name) . 'Form';
            if (class_exists($class)) {
                $instance = new $class($obj);
                if ($instance instanceof \Xoops\Form\Form) {
                    return $instance;
                }
            }
        }
        return false;
    }

    /**
     * Get Module Helper
     *
     * @param string $dirname dirname of module
     *
     * @return bool|Xoops\Module\Helper\HelperAbstract
     */
    public static function getModuleHelper($dirname)
    {
        return \Xoops\Module\Helper::getHelper($dirname);
    }

    /**
     * XOOPS language loader wrapper
     * Temporary solution, not encouraged to use
     *
     * @param string $name     Name of language file to be loaded, without extension
     * @param mixed  $domain   string: Module dirname; global language file will be loaded if
     *                           $domain is set to 'global' or not specified
     *                          array:  example; array('Frameworks/moduleclasses/moduleadmin')
     * @param string $language Language to be loaded, current language content will be loaded if not specified
     *
     * @return  boolean
     */
    public function loadLanguage($name, $domain = '', $language = null)
    {
        if (empty($name)) {
            return false;
        }

        $language = empty($language) ? XoopsLocale::getLegacyLanguage() : $language;
        // expanded domain to multiple categories, e.g. module:Fsystem, framework:filter, etc.
        if ((empty($domain) || 'global' === $domain)) {
            $path = '';
        } else {
            $path = (is_array($domain)) ? array_shift($domain) . '/' : "modules/{$domain}/";
        }
        $path .= 'language';

        if (!XoopsLoad::fileExists($file = $this->path("{$path}/{$language}/{$name}.php"))) {
            if (!XoopsLoad::fileExists($file = $this->path("{$path}/english/{$name}.php"))) {
                return false;
            }
        }
        $ret = include_once $file;
        return $ret;
    }

    /**
     * loadLocale
     *
     * @param string $domain Module dirname; global language file will be loaded if set to 'global' or not specified
     * @param string $locale Locale to be loaded, current language content will be loaded if not specified
     *
     * @return  boolean
     */
    public static function loadLocale($domain = null, $locale = null)
    {
        return \Xoops\Locale::loadLocale($domain, $locale);
    }

    /**
     * Translate a key value
     *
     * @param string $key     constant name
     * @param string $dirname dirname of module (domain)
     *
     * @param array $params   array of params used by this key
     * @return string
     */
    public function translate($key, $dirname = 'xoops', $params = [])
    {
        return \Xoops\Locale::translate($key, $dirname, $params);
    }

    /**
     * Get active modules from cache file
     *
     * @return array
     */
    public function getActiveModules()
    {
        if (is_array($this->activeModules)) {
            return $this->activeModules;
        }

        try {
            if (!$this->activeModules = $this->cache()->read('system/modules/active')) {
                $this->setActiveModules();
            }
        } catch (\Exception $e) {
            $this->activeModules = array();
        }
        return $this->activeModules;
    }

    /**
     * Write active modules to cache file
     *
     * @return array
     */
    public function setActiveModules()
    {
        $module_handler = $this->getHandlerModule();
        $modules_array = $module_handler->getAll(new Criteria('isactive', 1), array('dirname'), false, false);
        $modules_active = array();
        foreach ($modules_array as $module) {
            $modules_active[$module['mid']] = $module['dirname'];
        }
        $this->cache()->write('system/modules/active', $modules_active);
        $this->activeModules = $modules_active;
        return $modules_active;
    }

    /**
     * Checks is module is installed and active
     *
     * @param string $dirname module directory
     *
     * @return bool
     */
    public function isActiveModule($dirname)
    {
        if (isset($dirname) && in_array($dirname, $this->getActiveModules())) {
            return true;
        }
        return false;
    }

    /**
     * get module object from module name (dirname)
     *
     * @param string $dirname dirname of the module
     *
     * @return bool|XoopsModule
     */
    public function getModuleByDirname($dirname)
    {
        $key = "system/module/dirname/{$dirname}";
        if (!$module = $this->cache()->read($key)) {
            $module = $this->getHandlerModule()->getByDirname($dirname);
            $this->cache()->write($key, $module);
        }
        return $module;
    }

    /**
     * Get Module By Id
     *
     * @param int $id Id of the module
     *
     * @return bool|XoopsModule
     */
    public function getModuleById($id)
    {
        $key = "system/module/id/{$id}";
        if (!$module = $this->cache()->read($key)) {
            $module = $this->getHandlerModule()->getById($id);
            $this->cache()->write($key, $module);
        }
        return $module;
    }

    /**
     * Render Simple Header
     *
     * @param bool $closehead true to close the HTML head element
     *
     * @return void
     */
    public function simpleHeader($closehead = true)
    {
        $this->events()->triggerEvent('core.header.start');
        $this->theme();
        $xoopsConfigMetaFooter = $this->getConfigs();

        if (!headers_sent()) {
            header('Content-Type:text/html; charset=' . XoopsLocale::getCharset());
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            header(
                'Cache-Control: no-store, no-cache, max-age=1, s-maxage=1, must-revalidate, post-check=0, pre-check=0'
            );
            header("Pragma: no-cache");
        }

        echo "<!DOCTYPE html>\n";
        $xoops_url = \XoopsBaseConfig::get('url');
        echo '<html lang="' . XoopsLocale::getLangCode() . '">
              <head>
              <meta charset="utf-8">
              <meta http-equiv="X-UA-Compatible" content="IE=edge">
              <meta name="viewport" content="width=device-width, initial-scale=1">
              <meta name="robots" content="' . htmlspecialchars($xoopsConfigMetaFooter['meta_robots']) . '" />
              <meta name="keywords" content="' . htmlspecialchars($xoopsConfigMetaFooter['meta_keywords']) . '" />
              <meta name="description" content="' . htmlspecialchars($xoopsConfigMetaFooter['meta_description']) . '" />
              <meta name="rating" content="' . htmlspecialchars($xoopsConfigMetaFooter['meta_rating']) . '" />
              <meta name="author" content="' . htmlspecialchars($xoopsConfigMetaFooter['meta_author']) . '" />
              <meta name="generator" content="XOOPS" />
              <title>' . htmlspecialchars($this->getConfig('sitename')) . '</title>'
            . $this->theme->renderBaseAssets();

        $locale = $this->getConfig('locale');
        if (XoopsLoad::fileExists($this->path('locale/' . $locale . '/style.css'))) {
            echo '<link rel="stylesheet" type="text/css" media="all" href="' . $xoops_url
                . '/locale/' . $locale . '/style.css" />';
        }
        $themecss = $this->getCss($this->getConfig('theme_set'));
        if ($themecss) {
            echo '<link rel="stylesheet" type="text/css" media="all" href="' . $themecss . '" />';
        }
        if ($closehead) {
            echo '</head><body>';
        }
    }

    /**
     * Render simpleFooter
     *
     * @return void
     */
    public function simpleFooter()
    {
        $this->events()->triggerEvent('core.header.footer');
        echo '</body></html>';
        ob_end_flush();
    }
    /**
     * render an alert message to a string
     *
     * @param string $type  alert type, one of 'info', 'error', 'success' or 'warning'
     * @param mixed  $msg   string or array of strings
     * @param string $title title for alert
     *
     * @return string
     */
    public function alert($type, $msg, $title = '/')
    {
        $tpl = new XoopsTpl();
        switch ($type) {
            case 'info':
            default:
                $tpl->assign('alert_type', 'alert-info');
                if ($title === '/') {
                    $title = XoopsLocale::INFORMATION;
                }
                break;

            case 'error':
                $tpl->assign('alert_type', 'alert-danger');
                if ($title === '/') {
                    $title = XoopsLocale::ERROR;
                }
                break;

            case 'success':
                $tpl->assign('alert_type', 'alert-success');
                if ($title === '/') {
                    $title = XoopsLocale::SUCCESS;
                }
                break;

            case 'warning':
                $tpl->assign('alert_type', 'alert-warning');
                if ($title === '/') {
                    $title = XoopsLocale::WARNING;
                }
                break;
        }

        if ($title != '') {
            $tpl->assign('alert_title', $title);
        }
        if (!is_scalar($msg) && !is_array($msg)) {
            $msg = ''; // don't know what to do with this, so make it blank
        }
        $alert_msg = (is_array($msg)) ? @implode("<br />", $msg) : $msg;

        if (empty($alert_msg)) {
            return '';
        }
        $tpl->assign('alert_msg', $alert_msg);
        $ret = $tpl->fetch('module:system/system_alert.tpl');
        return $ret;

    }

    /**
     * Render a confirmation form to a string
     *
     * @param array   $hiddens  associative array of values used to complete confirmed action
     * @param string  $action   form action (URL)
     * @param string  $msg      message to display
     * @param string  $submit   submit button message
     * @param boolean $addtoken true to add CSRF token
     *
     * @return string rendered confirm message
     */
    public function confirm($hiddens, $action, $msg, $submit = '', $addtoken = true)
    {
        $tpl = new XoopsTpl();
        $submit = ($submit != '') ? trim($submit) : XoopsLocale::A_SUBMIT;
        $tpl->assign('msg', $msg);
        $tpl->assign('action', $action);
        $tpl->assign('submit', $submit);
        $str_hiddens = '';
        foreach ($hiddens as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $caption => $newvalue) {
                    $str_hiddens .= '<input type="radio" name="' . $name . '" value="'
                        . htmlspecialchars($newvalue) . '" > ' . $caption . NWLINE;
                }
                $str_hiddens .= '<br />' . NWLINE;
            } else {
                $str_hiddens .= '<input type="hidden" name="' . $name . '" value="'
                    . htmlspecialchars($value) . '" />' . NWLINE;
            }
        }
        if ($addtoken != false) {
            $tpl->assign('token', $this->security()->getTokenHTML());
        }
        $tpl->assign('hiddens', $str_hiddens);
        return $tpl->fetch('module:system/system_confirm.tpl');
    }

    /**
     * Get User Timestamp (kind of pointless, since timestamps are UTC?)
     *
     * @param \DateTime|int $time DateTime object or unix timestamp
     *
     * @return int unix timestamp
     */
    public function getUserTimestamp($time)
    {
        $dt = \Xoops\Core\Locale\Time::cleanTime($time);
        return $dt->getTimestamp();
    }

    /**
     * Function to calculate server timestamp from user entered time (timestamp)
     *
     * @param int  $timestamp time stamp
     * @param null $userTZ    timezone
     *
     * @return int
     */
    public function userTimeToServerTime($timestamp, $userTZ = null)
    {
        if (!isset($userTZ)) {
            $userTZ = $this->getConfig('default_TZ');
        }
        $timestamp = (float) $timestamp - (((float) $userTZ - (float) $this->getConfig('server_TZ')) * 3600);
        return (int)$timestamp;
    }

    /**
     * get the groups associated with the current user
     *
     * @return int[]
     */
    public function getUserGroups()
    {
        $groups = $this->isUser() ? $this->user->getGroups() : array(FixedGroups::ANONYMOUS);

        return $groups;
    }

    /**
     * generate a temporary password
     *
     * @return string
     *
     * @todo make better passwords
     */
    public function makePass()
    {
        $makepass = '';
        $syllables = array(
            'er', 'in', 'tia', 'wol', 'fe', 'pre', 'vet', 'jo', 'nes', 'al', 'len', 'son', 'cha', 'ir', 'ler', 'bo',
            'ok', 'tio', 'nar', 'sim', 'ple', 'bla', 'ten', 'toe', 'cho', 'co', 'lat', 'spe', 'ak', 'er', 'po', 'co',
            'lor', 'pen', 'cil', 'li', 'ght', 'wh', 'at', 'the', 'he', 'ck', 'is', 'mam', 'bo', 'no', 'fi', 've', 'any',
            'way', 'pol', 'iti', 'cs', 'ra', 'dio', 'sou', 'rce', 'sea', 'rch', 'pa', 'per', 'com', 'bo', 'sp', 'eak',
            'st', 'fi', 'rst', 'gr', 'oup', 'boy', 'ea', 'gle', 'tr', 'ail', 'bi', 'ble', 'brb', 'pri', 'dee', 'kay',
            'en', 'be', 'se'
        );
        for ($count = 1; $count <= 4; ++$count) {
            if (1 == mt_rand() % 10) {
                $makepass .= sprintf('%0.0f', (rand() % 50) + 1);
            } else {
                $makepass .= sprintf('%s', $syllables[rand() % 62]);
            }
        }
        return $makepass;
    }

    /**
     * Check Email
     *
     * @param string $email    check email
     * @param bool   $antispam true if returned email should be have anti-SPAM measures applied
     *
     * @return false|string email address if valid, otherwise false
     */
    public function checkEmail($email, $antispam = false)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        if ($antispam) {
            $email = str_replace("@", " at ", $email);
            $email = str_replace(".", " dot ", $email);
        }
        return $email;
    }

    /**
     * formatURL - add default http:// if no valid protocol specified
     *
     * @param string $url full or partial url
     *
     * @return string
     */
    public function formatURL($url)
    {
        $url = trim($url);
        if ($url != '') {
            if (!preg_match('/^(https?|ftps?|ed2k)\:\/\//i', $url)) {
                $url = 'http://' . $url;
            }
        }
        return $url;
    }

    /**
     * Function to get banner html tags for use in templates
     *
     * @return string
     */
    public function getBanner()
    {
        $options = '';
        $this->events()->triggerEvent('core.banner.display', array(&$options));
        return $options;
    }

    /**
     * Function to redirect a user to certain pages
     *
     * @param string $url               URL to redirect to
     * @param int    $time              time to wait (to allow reading message display)
     * @param string $message           message to display
     * @param bool   $addredirect       add xoops_redirect parameter with current URL to the redirect
     *                                   URL -  used for return from login redirect
     * @param bool   $allowExternalLink allow redirect to external URL
     *
     * @return void
     */
    public function redirect($url, $time = 3, $message = '', $addredirect = true, $allowExternalLink = false)
    {
        $this->events()->triggerEvent('core.redirect.start', array(
            $url, $time, $message, $addredirect, $allowExternalLink
        ));
        // if conditions are right, system preloads will exit on this call
        // so don't use it if you want to be called, use start version above.
        $this->events()->triggerEvent('core.include.functions.redirectheader', array(
            $url, $time, $message, $addredirect, $allowExternalLink
        ));

        $xoops_url = \XoopsBaseConfig::get('url');

        if (preg_match("/[\\0-\\31]|about:|script:/i", $url)) {
            if (!preg_match('/^\b(java)?script:([\s]*)history\.go\(-[0-9]*\)([\s]*[;]*[\s]*)$/si', $url)) {
                $url = $xoops_url;
            }
        }
        if (!$allowExternalLink && $pos = strpos($url, '://')) {
            $xoopsLocation = substr($xoops_url, strpos($xoops_url, '://') + 3);
            if (strcasecmp(substr($url, $pos + 3, strlen($xoopsLocation)), $xoopsLocation)) {
                $url = $xoops_url;
            }
        }
        if (!defined('XOOPS_CPFUNC_LOADED')) {
            $theme = 'default';
        } else {
            $theme = $this->getConfig('theme_set');
        }

        $xoopsThemeFactory = null;
        $xoopsThemeFactory = new \Xoops\Core\Theme\Factory();
        $xoopsThemeFactory->allowedThemes = $this->getConfig('theme_set_allowed');
        $xoopsThemeFactory->defaultTheme = $theme;
        $this->setTheme($xoopsThemeFactory->createInstance(array(
            "plugins" => array(), "renderBanner" => false
        )));
        $this->setTpl($this->theme()->template);
        $this->tpl()->assign(array(
            'xoops_theme'      => $theme, 'xoops_imageurl' => \XoopsBaseConfig::get('themes-url') . '/' . $theme . '/',
            'xoops_themecss'   => $this->getCss($theme),
            'xoops_requesturi' => htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES),
            'xoops_sitename'   => htmlspecialchars($this->getConfig('sitename'), ENT_QUOTES),
            'xoops_slogan'     => htmlspecialchars($this->getConfig('slogan'), ENT_QUOTES),
            'xoops_dirname'    => $this->isModule() ? $this->module->getVar('dirname') : 'system',
            'xoops_pagetitle'  => $this->isModule() ? $this->module->getVar('name')
                : htmlspecialchars($this->getConfig('slogan'), ENT_QUOTES)
        ));

        $this->tpl()->assign('time', (int)($time));
        if (!empty($_SERVER['REQUEST_URI']) && $addredirect && strstr($url, 'user.php')) {
            $joiner = (false===strpos($url, '?')) ? '?' : '&amp;';
            $url .= $joiner . 'xoops_redirect=' . urlencode($_SERVER['REQUEST_URI']);
        }
        $url = preg_replace("/&amp;/i", '&', htmlspecialchars($url, ENT_QUOTES));
        $this->tpl()->assign('url', $url);
        $message = trim($message) != '' ? $message : XoopsLocale::E_TAKING_YOU_BACK;
        $this->tpl()->assign('message', $message);
        $this->tpl()->assign('lang_ifnotreload', sprintf(XoopsLocale::F_IF_PAGE_NOT_RELOAD_CLICK_HERE, $url));

        $this->events()->triggerEvent('core.include.functions.redirectheader.end');
        $this->tpl()->display('module:system/system_redirect.tpl');
        exit();
    }

    /**
     * Do an immediate redirect to the specified url. Use this instead of using PHP's header()
     * directly so that a core.redirect.start event is triggered. An example is debugbar, that
     * stacks data so the details for both original and redirected scripts data are available.
     *
     * @param string $url URL to redirect to
     *
     * @return void
     */
    public static function simpleRedirect($url)
    {
        header("location: {$url}");
        $xoops = \Xoops::getInstance();
        $xoops->events()->triggerEvent('core.redirect.start', array($url));
        exit;
    }

    /**
     * Get Environment Value
     *
     * @param string $key key (name) in the environment
     *
     * @return string
     */
    public function getEnv($key)
    {
        return HttpRequest::getInstance()->getEnv($key, '');
    }

    /**
     * Function to get css file for a certain themeset
     *
     * @param string $theme theme name
     *
     * @return string
     */
    public function getCss($theme = '')
    {
        if ($theme == '') {
            $theme = $this->getConfig('theme_set');
        }
        $userAgent = $this->getEnv('HTTP_USER_AGENT');
        if (stristr($userAgent, 'mac')) {
            $str_css = 'styleMAC.css';
        } elseif (preg_match("/MSIE ([0-9]\.[0-9]{1,2})/i", $userAgent)) {
            $str_css = 'style.css';
        } else {
            $str_css = 'styleNN.css';
        }
        $xoops_theme_path = \XoopsBaseConfig::get('themes-path');
        $xoops_theme_url = \XoopsBaseConfig::get('themes-url');
        if (is_dir($xoops_theme_path . '/' . $theme)) {
            if (XoopsLoad::fileExists($xoops_theme_path . '/' . $theme . '/' . $str_css)) {
                return $xoops_theme_url . '/' . $theme . '/' . $str_css;
            } elseif (XoopsLoad::fileExists($xoops_theme_path . '/' . $theme . '/style.css')) {
                return $xoops_theme_url . '/' . $theme . '/style.css';
            }
        }
        if (is_dir($xoops_theme_path . '/' . $theme . '/css')) {
            if (XoopsLoad::fileExists($xoops_theme_path . '/' . $theme . '/css/' . $str_css)) {
                return $xoops_theme_url . '/' . $theme . '/css/' . $str_css;
            } elseif (XoopsLoad::fileExists($xoops_theme_path . '/' . $theme . '/css/style.css')) {
                return $xoops_theme_url . '/' . $theme . '/css/style.css';
            }
        }
        if (is_dir($xoops_theme_path . '/' . $theme . '/assets/css')) {
            if (XoopsLoad::fileExists($xoops_theme_path . '/' . $theme . '/assets/css/' . $str_css)) {
                return $xoops_theme_url . '/' . $theme . '/assets/css/' . $str_css;
            } elseif (XoopsLoad::fileExists($xoops_theme_path . '/' . $theme . '/assets/css/style.css')) {
                return $xoops_theme_url . '/' . $theme . '/assets/css/style.css';
            }
        }
        return '';
    }

    /**
     * Get Mailer
     *
     * @return XoopsMailer|XoopsMailerLocale
     */
    public function getMailer()
    {
        static $mailer;
        if (is_object($mailer)) {
            return $mailer;
        }
        \Xoops\Locale::loadMailerLocale();
        if (class_exists('XoopsMailerLocale')) {
            $mailer = new XoopsMailerLocale();
        } else {
            $mailer = new XoopsMailer();
        }
        return $mailer;
    }

    /**
     * Get Option
     *
     * @param string $key key (name) of option
     *
     * @return string
     */
    public function getOption($key)
    {
        $ret = '';
        if (isset($this->option[$key])) {
            $ret = $this->option[$key];
        }
        return $ret;
    }

    /**
     * Set Option
     *
     * @param string $key   key (name) of option
     * @param null   $value value for option
     *
     * @return void
     */
    public function setOption($key, $value = null)
    {
        if (!is_null($value)) {
            $this->option[$key] = $value;
        }
    }

    /**
     * Get Config value
     *
     * @param string $key key (name) of configuration
     *
     * @return mixed
     */
    public function getConfig($key)
    {
        return $this->getModuleConfig($key, 'system');
    }

    /**
     * Get all Config Values
     *
     * @return array
     */
    public function getConfigs()
    {
        return $this->getModuleConfigs('system');
    }

    /**
     * Add Config Values
     *
     * @param array  $configs array of configs
     * @param string $dirname module name
     *
     * @return void
     */
    public function addConfigs($configs, $dirname = 'system')
    {
        $dirname = trim(strtolower($dirname));
        if (empty($dirname)) {
            $dirname = $this->isModule() ? $this->module->getVar('dirname') : 'system';
        }
        if (!empty($dirname)) {
            $this->moduleConfigs[$dirname] = array_merge($this->moduleConfigs[$dirname], (array)$configs);
        }
    }

    /**
     * Set Config Value
     *
     * @param string $key     key (name) of the configuration item
     * @param mixed  $value   configuration value
     * @param string $dirname dirname of module
     *
     * @return void
     */
    public function setConfig($key, $value = null, $dirname = 'system')
    {
        if (!is_null($value)) {
            $dirname = trim(strtolower($dirname));
            if (empty($dirname)) {
                $dirname = $this->isModule() ? $this->module->getVar('dirname') : 'system';
            }
            $this->moduleConfigs[$dirname][$key] =& $value;
        }
    }

    /**
     * Unset Config Value
     *
     * @param string $key     key (name) of the configuration item
     * @param string $dirname dirname of module
     *
     * @return void
     */
    public function unsetConfig($key, $dirname = 'system')
    {
        $dirname = trim(strtolower($dirname));
        if (empty($dirname)) {
            $dirname = $this->isModule() ? $this->module->getVar('dirname') : 'system';
        }
        unset($this->moduleConfigs[$dirname][$key]);
        if (empty($this->moduleConfigs[$dirname])) {
            unset($this->moduleConfigs[$dirname]);
        }
    }

    /**
     * Unset all module configs
     *
     * @return void
     */
    public function clearModuleConfigsCache()
    {
        $this->moduleConfigs = array();
    }

    /**
     * getModuleConfig
     *
     * @param string $key     config name
     * @param string $dirname module directory
     *
     * @return mixed the value for the named config
     */
    public function getModuleConfig($key, $dirname = '')
    {
        $dirname = trim(strtolower($dirname));
        if (empty($dirname)) {
            $dirname = $this->isModule() ? $this->module->getVar('dirname') : 'system';
        }

        if (isset($this->moduleConfigs[$dirname][$key])) {
            return $this->moduleConfigs[$dirname][$key];
        }

        $this->getModuleConfigs($dirname);

        if (!isset($this->moduleConfigs[$dirname][$key])) {
            $this->moduleConfigs[$dirname][$key] = '';
        }
        return $this->moduleConfigs[$dirname][$key];
    }

    /**
     * Get Module Configs
     *
     * @param string $dirname dirname of module
     *
     * @return array
     */
    public function getModuleConfigs($dirname = '')
    {
        $dirname = trim($dirname);
        if (empty($dirname)) {
            $dirname = $this->isModule() ? $this->module->getVar('dirname') : 'system';
        }
        if (isset($this->moduleConfigs[$dirname])) {
            return $this->moduleConfigs[$dirname];
        }
        $this->moduleConfigs[$dirname] = array();
        $key = "system/module/configs/{$dirname}";
        if (!$configs = $this->cache()->read($key)) {
            $module = $this->getModuleByDirname($dirname);
            if (is_object($module)) {
                $configs = $this->getHandlerConfig()->getConfigsByModule($module->getVar('mid'));
                $this->cache()->write($key, $configs);
                $this->moduleConfigs[$dirname] =& $configs;
            }
        } else {
            $this->moduleConfigs[$dirname] =& $configs;
        }

        if ($this->isModule()) {
            //for legacy
            $this->moduleConfig =& $this->moduleConfigs[$this->module->getVar('dirname')];
        }
        if ($dirname === 'system') {
            $this->config =& $this->moduleConfigs['system'];
        }
        return $this->moduleConfigs[$dirname];
    }

    /**
     * Append Config Value
     *
     * @param string $key           key (name) of the configuration item
     * @param array  $values        array of configuration value
     * @param bool   $appendWithKey true to add each $value element with associative value
     *                               false to add $values as a single index element
     * @param string $dirname       dirname of module
     *
     * @return void
     */
    public function appendConfig($key, array $values, $appendWithKey = false, $dirname = 'system')
    {
        $dirname = trim(strtolower($dirname));
        if (empty($dirname)) {
            $dirname = $this->isModule() ? $this->module->getVar('dirname') : 'system';
        }
        if (!isset($this->moduleConfigs[$dirname][$key]) || !is_array($this->moduleConfigs[$dirname][$key])) {
            $this->moduleConfigs[$dirname][$key] = array();
        }
        if ($appendWithKey) {
            foreach ($values as $key2 => $value) {
                $this->moduleConfigs[$dirname][$key][$key2] =& $value;
            }
        } else {
            $this->moduleConfigs[$dirname][$key][] =& $values;
        }
    }

    /**
     * Disables page cache by overriding module cache settings
     *
     * @return void
     */
    public function disableModuleCache()
    {
        if ($this->isModule()) {
            $this->appendConfig('module_cache', array($this->module->getVar('mid') => 0), true);
        }
    }

    /**
     * getBaseDomain
     *
     * Get domain name from a URL. This will check that the domain is valid for registering,
     * preventing return of constructs like 'co.uk' as the domain. See https://publicsuffix.org/
     *
     * @param string  $url              URL
     * @param boolean $includeSubdomain true to include include subdomains,
     *                                  default is false registerable domain only
     *
     * @return string|null domain, or null if domain is invalid
     */
    public function getBaseDomain($url, $includeSubdomain = false)
    {
        $url=mb_strtolower($url, 'UTF-8');

        $host = parse_url($url, PHP_URL_HOST);
        if (empty($host)) {
            $host = parse_url($url, PHP_URL_PATH); // bare host name
            if (empty($host)) {
                return null;
            }
        }

        // check for exceptions, localhost and ip address (v4 & v6)
        if ($host==='localhost') {
            return $host;
        }
        // Check for IPV6 URL (see http://www.ietf.org/rfc/rfc2732.txt)
        // strip brackets before validating
        if (substr($host, 0, 1)==='[' && substr($host, -1)===']') {
            $host = substr($host, 1, (strlen($host)-2));
        }
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $host;
        }

        $regdom = new \Geekwright\RegDom\RegisteredDomain();
        $regHost = $regdom->getRegisteredDomain($host);
        if (null === $regHost) {
            return null;
        }
        return $includeSubdomain ? $host : $regHost;
    }

    /**
     * function to update compiled template file in cache folder
     *
     * @param string $tpl_id template id
     *
     * @return boolean
     */
    public function templateTouch($tpl_id)
    {
        $tplfile = $this->getHandlerTplFile()->get($tpl_id);

        if (is_object($tplfile)) {
            $file = $tplfile->getVar('tpl_file', 'n');
            $module = $tplfile->getVar('tpl_module', 'n');
            $type = $tplfile->getVar('tpl_type', 'n');
            $tpl = new XoopsTpl();
            return $tpl->touch($type . ':' . $module . '/' . $file);
        }
        return false;
    }

    /**
     * Clear the module cache
     *
     * @param int $mid Module ID
     *
     * @return void
     */
    public function templateClearModuleCache($mid)
    {
        $module = $this->getModuleById($mid);
        $xoopsTpl = new XoopsTpl();
        $xoopsTpl->clearModuleCompileCache($module->getVar('dirname'));
    }

    /**
     * Support for deprecated messages events
     *
     * @param string $message message
     *
     * @return void
     */
    public function deprecated($message)
    {
        $message = $this->logger()->sanitizePath($message);
        $this->events()->triggerEvent('core.deprecated', array($message));
    }

    /**
     * Support for disabling error reporting
     *
     * @return void
     */
    public function disableErrorReporting()
    {
        //error_reporting(0);
        $this->events()->triggerEvent('core.disableerrorreporting');
    }
}
