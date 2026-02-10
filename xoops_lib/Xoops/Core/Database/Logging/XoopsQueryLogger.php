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

/**
 * Query logger for XOOPS - replaces Doctrine DBAL 3.x DebugStack.
 *
 * Accumulates query timing data and fires XOOPS events on query start/stop.
 *
 * @category  Xoops\Core\Database\Logging
 * @package   Xoops\Core
 * @author    XOOPS Development Team
 * @copyright 2024 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
class XoopsQueryLogger
{
    /**
     * @var int Current query index
     */
    public int $currentQuery = 0;

    /**
     * @var array Executed queries with timing data
     */
    public array $queries = [];

    /**
     * @var bool Whether logging is enabled
     */
    public bool $enabled = true;

    /**
     * @var float|null Start time of current query
     */
    private ?float $start = null;

    /**
     * Mark the start of a query
     *
     * @param string     $sql    SQL statement
     * @param array|null $params Bound parameters
     * @param array|null $types  Parameter types
     *
     * @return void
     */
    public function startQuery(string $sql, ?array $params = null, ?array $types = null): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->start = microtime(true);
        $this->queries[++$this->currentQuery] = [
            'sql'         => $sql,
            'params'      => $params,
            'types'       => $types,
            'executionMS' => 0,
        ];

        if (class_exists('\Xoops', false)) {
            \Xoops::getInstance()->events()->triggerEvent(
                'core.database.query.begin',
                [$sql, $params, $types]
            );
        }
    }

    /**
     * Mark the end of a query
     *
     * @return void
     */
    public function stopQuery(): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->queries[$this->currentQuery]['executionMS'] =
            microtime(true) - $this->start;

        if (class_exists('\Xoops', false)) {
            \Xoops::getInstance()->events()->triggerEvent(
                'core.database.query.complete',
                $this->queries[$this->currentQuery]
            );
        }
    }
}
