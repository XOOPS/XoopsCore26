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

use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\ParameterType;

/**
 * DBAL 4.x logging statement wrapper for XOOPS.
 *
 * Intercepts prepared statement execution to log queries through XoopsQueryLogger.
 *
 * @category  Xoops\Core\Database\Logging
 * @package   Xoops\Core
 * @author    XOOPS Development Team
 * @copyright 2024 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
final class XoopsLoggingStatement extends AbstractStatementMiddleware
{
    private XoopsQueryLogger $queryLogger;
    private string $sql;

    /** @var array<int|string, mixed> bound parameter values */
    private array $params = [];

    /** @var array<int|string, ParameterType> bound parameter types */
    private array $types = [];

    /**
     * @param \Doctrine\DBAL\Driver\Statement $statement   the wrapped statement
     * @param XoopsQueryLogger                $queryLogger the query logger instance
     * @param string                          $sql         the SQL being prepared
     */
    public function __construct(
        \Doctrine\DBAL\Driver\Statement $statement,
        XoopsQueryLogger $queryLogger,
        string $sql
    ) {
        parent::__construct($statement);
        $this->queryLogger = $queryLogger;
        $this->sql = $sql;
    }

    /**
     * {@inheritDoc}
     */
    public function bindValue(int|string $param, mixed $value, ParameterType $type = ParameterType::STRING): void
    {
        $this->params[$param] = $value;
        $this->types[$param] = $type;
        parent::bindValue($param, $value, $type);
    }

    /**
     * {@inheritDoc}
     */
    public function execute(): Result
    {
        $this->queryLogger->startQuery($this->sql, $this->params, $this->types);
        try {
            $result = parent::execute();
        } finally {
            $this->queryLogger->stopQuery();
        }
        return $result;
    }
}
