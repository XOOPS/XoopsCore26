<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

namespace Xoops\Core\Database\Logging;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware as MiddlewareInterface;

/**
 * DBAL 4.x Middleware for XOOPS query logging.
 *
 * Replaces the old setSQLLogger() approach used in DBAL 3.x.
 *
 * @category  Xoops\Core\Database\Logging
 * @package   Xoops\Core
 * @author    XOOPS Development Team
 * @copyright 2024 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
final class XoopsLoggingMiddleware implements MiddlewareInterface
{
    private XoopsQueryLogger $queryLogger;

    /**
     * @param XoopsQueryLogger $queryLogger the query logger instance
     */
    public function __construct(XoopsQueryLogger $queryLogger)
    {
        $this->queryLogger = $queryLogger;
    }

    /**
     * {@inheritDoc}
     */
    public function wrap(DriverInterface $driver): DriverInterface
    {
        return new XoopsLoggingDriver($driver, $this->queryLogger);
    }
}
