<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

use Xmf\Yaml;

/**
 * XoopsBaseConfig holds the base XOOPS configs needed to locate key paths and
 * enable database access
 *
 * @category  XoopsBaseConfig
 * @package   XoopsBaseConfig
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2015 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      http://xoops.org
 */
class XoopsBaseConfig
{
    /**
     * @var string[] $configs
     */
    private static $configs = array();

    /**
     * __construct
     * @param string|string[] $config fully qualified name of configuration file
     *                                or configuration array
     * @throws Exception
     */
    private function __construct($config)
    {
        if (!class_exists('XoopsLoad', false)) {
            include __DIR__ . '/xoopsload.php';
        }
        if (is_string($config)) {
            $yamlString = file_get_contents($config);
            if ($yamlString === false) {
                throw new \Exception('XoopsBaseConfig failed to load configuration.');
            }
            $loaderPath = $this->extractLibPath($yamlString) . '/vendor/autoload.php';
            if (file_exists($loaderPath)) {
                include_once $loaderPath;
            }
            self::$configs = Yaml::loadWrapped($yamlString);
            \XoopsLoad::startAutoloader(self::$configs['lib-path']);
        } elseif (is_array($config)) {
            self::$configs = $config;
            \XoopsLoad::startAutoloader(self::$configs['lib-path']);
        }
        if (!isset(self::$configs['lib-path'])) {
            throw new \Exception('XoopsBaseConfig lib-path not defined.');
            return;
        }
        \XoopsLoad::startAutoloader(self::$configs['lib-path']);
    }

    /**
     * Allow one instance only!
     *
     * @param string|string[] $config fully qualified name of configuration file
     *                                or configuration array
     *
     * @return XoopsBaseConfig instance
     * @throws Exception
     */
    final public static function getInstance($config = '')
    {
        static $instance = false;

        if (!$instance && !empty($config)) {
            $instance = new \XoopsBaseConfig($config);
        }

        if ($instance === false || empty(self::$configs)) {
            throw new \Exception('XoopsBaseConfig failed.');
        }
        return $instance;
    }

    /**
     * extractLibPath - solve a which comes first, chicken or egg type problem
     *
     * The yaml file we can load has the path we need to set up the autoloader we need
     * to reach our yaml library. We solve this by looking through the raw yaml file
     * contents to locate our data. This works only because there is a unique key that
     * should not be duplicated in a limited and known data set.
     *
     * Not pretty, but this way we get full access to xoops from a single known path.
     *
     * @param string $filecontents contents of the yaml configuration file
     *
     * @return string the extracted lib-path value
     */
    private function extractLibPath($filecontents)
    {
        $match = array();
        $matched = preg_match('/[.\v]*^lib-path\h*\:\h*[\']?([^\'\v]*)[\']?\h*$[.\v]*/m', $filecontents, $match);

        return $matched ? trim($match[1]) : '';
    }

    /**
     * Retrieve an attribute value.
     *
     * @param string $name name of an attribute
     *
     * @return mixed value of the attribute, or null if not set.
     */
    final public static function get($name)
    {
        if (isset(self::$configs[$name])) {
            return self::$configs[$name];
        }
        //trigger_error('variable : '.$name.' not found!', E_USER_ERROR);
        return null;
    }

    /**
     * Verify an attribute is defined.
     *
     * @param string $name name of an attribute
     *
     * @return boolean true if attribute is defined, otherwise false.
     */
    final public static function has($name)
    {
        return isset(self::$configs[$name]);
    }

    /**
     * Get a copy of all base configurations
     *
     * @return array of of all attributes
     */
    final public function getAll()
    {
        return self::$configs;
    }

    /**
     * Establish backward compatibility defines
     *
     * @return void
     */
    final public static function establishBCDefines()
    {
        if (defined('XOOPS_ROOT_PATH')) {
            return;
        }

        // Physical path to the XOOPS documents (served) directory WITHOUT trailing slash
        define('XOOPS_ROOT_PATH', self::get('root-path'));

        // For forward compatibility
        // Physical path to the XOOPS library directory WITHOUT trailing slash
        define('XOOPS_PATH', self::get('lib-path'));
        // Physical path to the XOOPS datafiles (writable) directory WITHOUT trailing slash
        define('XOOPS_VAR_PATH', self::get('var-path'));
        // Alias of XOOPS_PATH, for compatibility, temporary solution
        define("XOOPS_TRUST_PATH", self::get('trust-path'));

        // URL Association for SSL and Protocol Compatibility
        define('XOOPS_PROT', self::get('prot'));

        // XOOPS Virtual Path (URL)
        // Virtual path to your main XOOPS directory WITHOUT trailing slash
        // Example: define('XOOPS_URL', 'http://localhost/xoopscore');
        define('XOOPS_URL', self::get('url'));

        // Secure file
        // require XOOPS_VAR_PATH . '/data/secure.php';

        // Database
        // Choose the database to be used
        define('XOOPS_DB_TYPE', self::get('db-type'));

        // Set the database charset if applicable
        define("XOOPS_DB_CHARSET", self::get('db-charset'));

        // Table Prefix
        // This prefix will be added to all new tables created to avoid name conflict in the database.
        define('XOOPS_DB_PREFIX', self::get('db-prefix'));

        // Database Hostname
        // Hostname of the database server. If you are unsure, "localhost" works in most cases.
        define('XOOPS_DB_HOST', self::get('db-host'));

        // Database Username
        // Your database user account on the host
        define('XOOPS_DB_USER', self::get('db-user'));

        // Database Password
        // Password for your database user account
        define('XOOPS_DB_PASS', self::get('db-pass'));

        // Database Name
        // The name of database on the host.
        define('XOOPS_DB_NAME', self::get('db-name'));

        // persistent connection is no longer supported
        define("XOOPS_DB_PCONNECT", self::get('db-pconnect'));

        // Serialized connection parameter
        // This is built by the installer and includes all connection parameters
        define('XOOPS_DB_PARAMETERS', serialize(self::get('db-parameters')));

        define('XOOPS_UPLOAD_PATH', self::get('uploads-path'));
        define('XOOPS_UPLOAD_URL', self::get('uploads-url'));
    }

    /**
     * Create a working environment from traditional mainfile environment
     *
     * For the early phases in the installer, these may not be defined. Until it
     * is converted we try and do the best we can without errors
     *
     * @return void
     */
    final public static function bootstrapTransition()
    {
        $path = self::defineDefault('XOOPS_ROOT_PATH', basename(__DIR__));
        $url = (defined('XOOPS_URL')) ?
            XOOPS_URL :
            ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === "on") ? 'https://' : 'http://')
            . $_SERVER['SERVER_NAME']
            . (($_SERVER['SERVER_PORT'] != '80') ? ':' . $_SERVER['SERVER_PORT'] : '');

        $parts = parse_url($url . '/');
        $host = isset($parts['host']) ? $parts['host'] : $_SERVER['SERVER_NAME'];
        $host = ($host==='localhost') ? '' : $host;
        $urlpath = isset($parts['path']) ? $parts['path'] : '/';

        $libpath = self::defineDefault('XOOPS_PATH');
        $varpath = self::defineDefault('XOOPS_VAR_PATH');

        $configs = array(
            'root-path' => $path,
            'lib-path' => $libpath,
            'var-path' => $varpath,
            'trust-path' => $libpath,
            'url' => $url,
            'prot' => self::defineDefault('XOOPS_PROT'),
            'asset-path' => $path . '/assets',
            'asset-url' => $url . '/assets',
            'themes-path' => $path .'/themes',
            'themes-url' => $url . '/themes',
            'adminthemes-path' => $path . '/modules/system/themes',
            'adminthemes-url' => $url . '/modules/system/themes',
            'media-path' => $path . '/media',
            'media-url' => $url . '/media',
            'uploads-path' => $path . '/uploads',
            'uploads-url' => $url . '/uploads',
            'cookie-domain' => $host,
            'cookie-path' => $urlpath,
            'smarty-cache' => $varpath . '/caches/smarty_cache',
            'smarty-compile' => $varpath . '/caches/smarty_compile',
            'smarty-xoops-plugins' => $libpath . '/smarty/xoops_plugins',
            'db-type' => self::defineDefault('XOOPS_DB_TYPE'),
            'db-charset' => 'utf8',
            'db-prefix' => self::defineDefault('XOOPS_DB_PREFIX'),
            'db-host' => self::defineDefault('XOOPS_DB_HOST'),
            'db-user' => self::defineDefault('XOOPS_DB_USER'),
            'db-pass' => self::defineDefault('XOOPS_DB_PASS'),
            'db-name' => self::defineDefault('XOOPS_DB_NAME'),
            'db-pconnect' => 0,
            'db-parameters' => defined('XOOPS_DB_PARAMETERS') ? unserialize(XOOPS_DB_PARAMETERS) : array(),
        );
        self::getInstance($configs);
    }

    /**
     * defineDefault - return a constant if it is defined, or a default value if not.
     * If no default is specified, the define name will be used if needed.
     *
     * @param string      $define  a define constant name
     * @param string|null $default default value to return if $define is not defined
     *
     * @return string value of define or default
     */
    private static function defineDefault($define, $default = null)
    {
        $default = ($default === null) ? $define : $default;
        $return = defined($define) ? constant($define) : $default;
        return $return;
    }
}
