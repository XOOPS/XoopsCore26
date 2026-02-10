<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

namespace Xoops\Core\Cache;

use Stash\Pool;
use Stash\Interfaces\DriverInterface;
use Xmf\Yaml;
use Xoops\Core\Cache\DriverList;
use Xoops\Core\Cache\Access;

/**
 * Provides a standardized cache access
 *
 * @category  Xoops\Core\Cache
 * @package   CacheManager
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2015 The XOOPS Project https://github.com/XOOPS
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      http://xoops.org
 */
class CacheManager
{
    /**
     * Cache Access objects for pools
     *
     * @var Access[]
     */
    protected $pools = [];

    /**
     * Pool definitions
     *
     * @var array
     */
    protected $poolDefs = [];

    /**
     * Xoops instance
     *
     * @var \Xoops
     */
    protected $xoops;

    /**
     * __construct
     */
    public function __construct()
    {
        $this->xoops = \Xoops::getInstance();
        $defaults = $this->getDefaults();
		$xoops_var_path = \XoopsBaseConfig::get('var-path');
		$cache_file = $xoops_var_path . '/configs/cache.php';
        $poolDefs = Yaml::readWrapped($cache_file);
        if (empty($poolDefs)) {
            Yaml::saveWrapped($defaults, $cache_file);
        }
        $poolDefs = is_array($poolDefs) ? $poolDefs : array();
        $this->poolDefs = array_merge($defaults, $poolDefs);
    }

    /**
     * getDefaults get default cache configuration used if there is no config file
     *
     * @return array cache configuration
     */
    private static function getDefaults()
    {

        $defaults = [
            'default' => [
                'driver' => 'Sqlite',
                'options' => ['path' => \XoopsBaseConfig::get('var-path') . '/stash/'],
                ],
            'temp' => [
                'driver' => 'Ephemeral',
                'options' => [],
                ],
            ];
        return $defaults;
    }

    /**
     * Create a default configuration file, used in installation
     *
     * SQLite is the recommended driver, and will be used by default if available.
     *
     * We will fall back to FileSystem if SQLite is not available.
     *
     * Note: some versions of the Stash FileSystem driver appear susceptible to
     * race conditions which may cause random failures.
     *
     * Note for Windows users:
     *
     * When using Windows NTFS, PHP has a maximum path length of 260 bytes. Each key level in a
     * Stash hierarchical key corresponds to a directory, and is normalized as an md5 hash. Also,
     * Stash uses 2 levels for its own integrity and locking mechanisms. The full key length used
     * in XoopCore can reach 202 characters.
     *
     * Installing the pdo_sqlite3 extension is highly recommended to avoid problems.
     *
     * @return void
     */
    public static function createDefaultConfig()
    {
        $configFile = \XoopsBaseConfig::get('var-path') . '/configs/cache.php';
        if (file_exists($configFile)) {
            return;
        }
        $defaults = self::getDefaults();
        if (!array_key_exists("SQLite", \Stash\DriverList::getAvailableDrivers())) {
            $defaults['default']['driver'] = 'FileSystem';
            $defaults['default']['options'] = [
                'dirSplit' => 1,
                'path' => \XoopsBaseConfig::get('var-path') . '/stash/'
            ];
            if (false !== stripos(PHP_OS, 'WIN')) {
                trigger_error("SQLite is strongly recommended on windows due to 260 character file path restrictions.");
            }
        }
        Yaml::saveWrapped($defaults, $configFile);
    }

    /**
     * Get a cache corresponding to the specified name
     *
     * @param string $name Name of cache definition
     *
     * @return Access object
     */
    public function getCache($name)
    {
        $pool = false;
        if (array_key_exists($name, $this->pools)) {
            $pool =  $this->pools[$name];
        } elseif (array_key_exists($name, $this->poolDefs)) {
            $pool =  $this->startPoolAccess($name);
        }
        if ($pool === false) {
            $pool = $this->getDefaultPool($name);
        }

        $this->pools[$name] = $pool;

        return $pool;
    }

    /**
     * Instantiate an Access object from named configuration, including
     * instantiating pool and driver
     *
     * @param string $name name of pool configuration to start
     *
     * @return Access|false pool or false if a pool cannot be created
     */
    protected function startPoolAccess($name)
    {
        $pool = false;
        $options = false;
        if (isset($this->poolDefs[$name]['options'])) {
            $options = $this->poolDefs[$name]['options'];
        }
        $driverName = $this->poolDefs[$name]['driver'];
        if (0 === strcasecmp($driverName, 'Composite')) {
            $drivers = array();
            foreach ($this->poolDefs[$name]['options']['drivers'] as $subDriver) {
                $drivers[] = $this->getDriver($subDriver['driver'], $subDriver['options']);
            }
            $options['drivers'] = $drivers;
        }

        $driver = $this->getDriver($driverName, $options);
        if ($driver!==false) {
            $pool = new Pool($driver);
            if (is_object($pool)) {
                $pool->setLogger($this->xoops->logger());
                try {
                    $prefix = $this->xoops->db()->prefix();
                } catch (\Throwable $e) {
                    $prefix = \XoopsBaseConfig::get('db-prefix') ?: 'xoops';
                }
                $pool->setNamespace($prefix);
            }
        }
        if (!$pool) {
            $this->xoops->logger()->warn('Could not create cache pool '.$name);
            return $pool;
        }

        return new Access($pool);
    }

    /**
     * getDriver
     *
     * @param string $driverName short name of the driver
     * @param array  $options    array of options for the driver
     *
     * @return DriverInterface|false driver object or false if it could not be instantiated
     */
    protected function getDriver($driverName, $options)
    {
        $driver = false;
        $driverClass = DriverList::getDriverClass($driverName);

        if ($driverClass!==false && $driverClass::isAvailable()) {
            $options = is_array($options) ? $options : array();
            $driver = new $driverClass($options);
        }
        return ($driver instanceof DriverInterface) ? $driver : false;
    }

    /**
     * Get an Access object based on the default pool. If it isn't set, create it.
     * If no definition exists for default, use Stash default (Ephimeral.)
     *
     * @param string $originalName originally requested pool configuration name
     *
     * @return Access object
     */
    protected function getDefaultPool($originalName)
    {
        $this->xoops->events()->triggerEvent('debug.log', 'Substituting default cache pool for '.$originalName);
        $name = 'default';
        if (array_key_exists($name, $this->pools)) {
            return $this->pools[$name];
        }
        $pool = $this->startPoolAccess($name);
        if ($pool===false) {
            $this->xoops->logger()->error('Could not create default cache pool');
            $pool = new Access(new \Stash\Pool());
        }
        $this->pools[$name] = $pool;
        return $pool;
    }
}
