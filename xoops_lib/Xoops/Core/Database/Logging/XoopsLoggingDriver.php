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

use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;

/**
 * DBAL 4.x logging driver wrapper for XOOPS.
 *
 * @category  Xoops\Core\Database\Logging
 * @package   Xoops\Core
 * @author    XOOPS Development Team
 * @copyright 2024 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
final class XoopsLoggingDriver extends AbstractDriverMiddleware
{
    private XoopsQueryLogger $queryLogger;

    /**
     * @param \Doctrine\DBAL\Driver $driver      the wrapped driver
     * @param XoopsQueryLogger      $queryLogger the query logger instance
     */
    public function __construct(\Doctrine\DBAL\Driver $driver, XoopsQueryLogger $queryLogger)
    {
        parent::__construct($driver);
        $this->queryLogger = $queryLogger;
    }

    /**
     * {@inheritDoc}
     */
    public function connect(
        #[\SensitiveParameter]
        array $params
    ): ConnectionInterface {
        return new XoopsLoggingConnection(
            parent::connect($params),
            $this->queryLogger
        );
    }
}
