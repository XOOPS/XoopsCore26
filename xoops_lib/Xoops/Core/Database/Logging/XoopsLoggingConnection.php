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
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;

/**
 * DBAL 4.x logging connection wrapper for XOOPS.
 *
 * Intercepts query and exec calls to log them through XoopsQueryLogger.
 *
 * @category  Xoops\Core\Database\Logging
 * @package   Xoops\Core
 * @author    XOOPS Development Team
 * @copyright 2024 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
final class XoopsLoggingConnection extends AbstractConnectionMiddleware
{
    private XoopsQueryLogger $queryLogger;

    /**
     * @param ConnectionInterface $connection  the wrapped connection
     * @param XoopsQueryLogger    $queryLogger the query logger instance
     */
    public function __construct(ConnectionInterface $connection, XoopsQueryLogger $queryLogger)
    {
        parent::__construct($connection);
        $this->queryLogger = $queryLogger;
    }

    /**
     * {@inheritDoc}
     */
    public function prepare(string $sql): Statement
    {
        return new XoopsLoggingStatement(
            parent::prepare($sql),
            $this->queryLogger,
            $sql
        );
    }

    /**
     * {@inheritDoc}
     */
    public function query(string $sql): Result
    {
        $this->queryLogger->startQuery($sql);
        try {
            $result = parent::query($sql);
        } finally {
            $this->queryLogger->stopQuery();
        }
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function exec(string $sql): int|string
    {
        $this->queryLogger->startQuery($sql);
        try {
            $result = parent::exec($sql);
        } finally {
            $this->queryLogger->stopQuery();
        }
        return $result;
    }
}
