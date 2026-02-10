<?php
/**
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xoops\Core\Database;

use Xoops\Core\Database\Logging\XoopsQueryLogger;
use Xoops\Core\Database\Logging\XoopsLoggingMiddleware;

/**
 * Xoops Database Factory class
 *
 * @category  Xoops\Class\Database\Factory
 * @package   Factory
 * @author    Kazumi Ono <onokazu@xoops.org>
 * @author    readheadedrod <redheadedrod@hotmail.com>
 * @copyright 2013-2024 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @version   Release:2.6
 * @link      http://xoops.org
 * @since     2.6.0
 */
class Factory
{
    /**
     * @var XoopsQueryLogger|null shared query logger instance
     */
    private static ?XoopsQueryLogger $queryLogger = null;

    /**
     * Get the query logger instance
     *
     * @return XoopsQueryLogger
     */
    public static function getQueryLogger(): XoopsQueryLogger
    {
        if (self::$queryLogger === null) {
            self::$queryLogger = new XoopsQueryLogger();
        }
        return self::$queryLogger;
    }

    /**
     * Get a reference to the only instance of database class and connects to DB
     *
     * if the class has not been instantiated yet, this will also take
     * care of that
     *
     * Doctrine connection function
     *
     * NOTE: persistent connections are not included. XOOPS_DB_PCONNECT is ignored.
     *
     * @param array $options driverOptions for Doctrine
     *
     * @return Connection|null Reference to the only instance of database class
     *
     * @todo change driver to support other databases and support for port, unix_socket and driver options.
     */
    public static function getConnection($options = null)
    {
        static $instance;
        if (!isset($instance)) {
            $xoops = \Xoops::getInstance();
            $queryLogger = self::getQueryLogger();
            $middleware = new XoopsLoggingMiddleware($queryLogger);
            $config = new \Doctrine\DBAL\Configuration();
            $config->setMiddlewares([$middleware]);

            $parameters = \XoopsBaseConfig::get('db-parameters');
            if (!empty($parameters) && is_array($parameters)) {
                $connectionParams = $parameters;
                $connectionParams['wrapperClass'] = '\Xoops\Core\Database\Connection';
            } else {
                $driver = 'pdo_' . \XoopsBaseConfig::get('db-type');
                $connectionParams = array(
                    'dbname' => \XoopsBaseConfig::get('db-name'),
                    'user' => \XoopsBaseConfig::get('db-user'),
                    'password' => \XoopsBaseConfig::get('db-pass'),
                    'host' => \XoopsBaseConfig::get('db-host'),
                    'charset' => \XoopsBaseConfig::get('db-charset'),
                    'driver' => $driver,
                    'wrapperClass' => '\Xoops\Core\Database\Connection',
                );
                // Support for other doctrine databases
                $xoops_db_port = \XoopsBaseConfig::get('db-port');
                if (!empty($xoops_db_port)) {
                    $connectionParams['port'] = $xoops_db_port;
                }
                $xoops_db_socket = \XoopsBaseConfig::get('db-socket');
                if (!empty($xoops_db_socket)) {
                    $connectionParams['unix_socket'] = $xoops_db_socket;
                }
                if (!is_null($options) && is_array($options)) {
                    $connectionParams['driverOptions'] = $options;
                }
            }

            $instance = \Doctrine\DBAL\DriverManager::getConnection(
                $connectionParams,
                $config
            );
            if (!defined('XOOPS_DB_PROXY') || ('GET' !== \Xmf\Request::getMethod()) || (php_sapi_name() === 'cli')) {
                $instance->setSafe(true);
            }
        }
        return $instance;
    }
}
