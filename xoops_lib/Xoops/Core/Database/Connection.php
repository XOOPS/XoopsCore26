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

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Result;

/**
 * Connection wrapper for Doctrine DBAL Connection
 *
 * @category  Xoops\Core\Database\Connection
 * @package   Connection
 * @author    readheadedrod <redheadedrod@hotmail.com>
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2013-2024 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @version   Release: 2.6
 * @link      http://xoops.org
 * @since     2.6.0
 */
class Connection extends \Doctrine\DBAL\Connection
{
    /**
     * @var bool $safe true means it is safe to write to database
     * removed allowedWebChanges as unnecessary. Using this instead.
     */
    protected $safe;


    /**
     * @var bool $force true means force writing to database even if safe is not true.
     */
    protected $force;

    /**
     * @var bool $transactionActive true means a transaction is in process.
     */
    protected $transactionActive;


    /**
     * this is a public setter for the safe variable
     *
     * @param bool $safe set safe to true if safe to write data to database
     *
     * @return void
     */
    public function setSafe($safe = true)
    {
        $this->safe = (bool)$safe;
    }

    /**
     * this is a public getter for the safe variable
     *
     * @return boolean
     */
    public function getSafe()
    {
        return $this->safe;
    }

    /**
     * this is a public setter for the $force variable
     *
     * @param bool $force when true will force a write to database when safe is false.
     *
     * @return void
     */
    public function setForce($force = false)
    {
        $this->force = (bool) $force;
    }

    /**
     * this is a public getter for the $force variable
     *
     * @return boolean
     */
    public function getForce()
    {
        return $this->force;
    }

    /**
     * Initializes a new instance of the Connection class.
     *
     * This sets up necessary variables before calling parent constructor
     *
     * @param array              $params Parameters for the driver
     * @param Driver             $driver The driver to use
     * @param Configuration|null $config The connection configuration
     */
    public function __construct(
        array $params,
        Driver $driver,
        ?Configuration $config = null
    ) {
        $this->safe = false;
        $this->force = false;
        $this->transactionActive = false;

        try {
            parent::__construct($params, $driver, $config);
        } catch (\Exception $e) {
            // We are dead in the water. This exception may contain very sensitive
            // information and cannot be allowed to be displayed as is.
            //\Xoops::getInstance()->events()->triggerEvent('core.exception', $e);
            trigger_error("Cannot get database connection", E_USER_ERROR);
        }
    }

    /**
     * Prepend the prefix.'_' to the given tablename
     * If tablename is empty, just return the prefix.
     *
     * @param string $tablename tablename
     *
     * @return string prefixed tablename, or prefix if tablename is empty
     */
    public function prefix($tablename = '')
    {
        $prefix = \XoopsBaseConfig::get('db-prefix');
        if ($tablename != '') {
            return $prefix . '_' . $tablename;
        } else {
            return $prefix;
        }
    }

    /**
     * Quote a column name with backticks for MySQL.
     *
     * DBAL 4.x does not quote column names in insert/update/delete methods,
     * which causes failures when column names collide with MySQL reserved words
     * (e.g. 'rank', 'level', 'order', 'key', 'group').
     *
     * @param string $columnName The column name to quote
     *
     * @return string The backtick-quoted column name
     */
    private function quoteColumnName(string $columnName): string
    {
        return '`' . str_replace('`', '``', $columnName) . '`';
    }

    /**
     * Re-key a data array so that column names are backtick-quoted.
     *
     * @param array $data Associative array of column => value
     *
     * @return array Re-keyed array with quoted column names
     */
    private function quoteDataKeys(array $data): array
    {
        $quoted = [];
        foreach ($data as $columnName => $value) {
            $quoted[$this->quoteColumnName($columnName)] = $value;
        }
        return $quoted;
    }

    /**
     * Inserts a table row with specified data.
     *
     * Overrides DBAL to quote column names with backticks, preventing
     * MySQL reserved word collisions (e.g. 'rank', 'level').
     *
     * @param string $table The name of the table to insert data into.
     * @param array  $data  An associative array containing column-value pairs.
     * @param array  $types Types of the inserted data.
     *
     * @return int|string The number of affected rows.
     */
    public function insert(string $table, array $data, array $types = []): int|string
    {
        if (count($data) === 0) {
            return $this->executeStatement('INSERT INTO ' . $table . ' () VALUES ()');
        }

        $columns = [];
        $values  = [];
        $set     = [];

        foreach ($data as $columnName => $value) {
            $columns[] = $this->quoteColumnName($columnName);
            $values[]  = $value;
            $set[]     = '?';
        }

        return $this->executeStatement(
            'INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ')'
            . ' VALUES (' . implode(', ', $set) . ')',
            $values,
            is_string(key($types)) ? $this->extractTypeValues(array_keys($data), $types) : $types,
        );
    }

    /**
     * Executes an SQL UPDATE statement on a table.
     *
     * Overrides DBAL to quote column names with backticks, preventing
     * MySQL reserved word collisions.
     *
     * @param string $table    The name of the table to update.
     * @param array  $data     An associative array containing column-value pairs.
     * @param array  $criteria The update criteria (column-value pairs for WHERE).
     * @param array  $types    Types of the merged $data and $criteria arrays.
     *
     * @return int|string The number of affected rows.
     */
    public function update(string $table, array $data, array $criteria = [], array $types = []): int|string
    {
        $columns = $values = $conditions = $set = [];

        foreach ($data as $columnName => $value) {
            $columns[] = $columnName;
            $values[]  = $value;
            $set[]     = $this->quoteColumnName($columnName) . ' = ?';
        }

        foreach ($criteria as $columnName => $value) {
            if ($value === null) {
                $conditions[] = $this->quoteColumnName($columnName) . ' IS NULL';
                continue;
            }
            $columns[]    = $columnName;
            $values[]     = $value;
            $conditions[] = $this->quoteColumnName($columnName) . ' = ?';
        }

        if (is_string(key($types))) {
            $types = $this->extractTypeValues($columns, $types);
        }

        $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $set);

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        return $this->executeStatement($sql, $values, $types);
    }

    /**
     * Executes an SQL DELETE statement on a table.
     *
     * Overrides DBAL to quote column names with backticks, preventing
     * MySQL reserved word collisions.
     *
     * @param string $table    The name of the table on which to delete.
     * @param array  $criteria The deletion criteria (column-value pairs for WHERE).
     * @param array  $types    The parameter types.
     *
     * @return int|string The number of affected rows.
     */
    public function delete(string $table, array $criteria = [], array $types = []): int|string
    {
        $columns = $values = $conditions = [];

        foreach ($criteria as $columnName => $value) {
            if ($value === null) {
                $conditions[] = $this->quoteColumnName($columnName) . ' IS NULL';
                continue;
            }
            $columns[]    = $columnName;
            $values[]     = $value;
            $conditions[] = $this->quoteColumnName($columnName) . ' = ?';
        }

        $sql = 'DELETE FROM ' . $table;

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        return $this->executeStatement(
            $sql,
            $values,
            is_string(key($types)) ? $this->extractTypeValues($columns, $types) : $types,
        );
    }

    /**
     * Inserts a table row with specified data.
     *
     * Adds prefix to the name of the table then passes to insert().
     *
     * @param string $tableName The name of the table to insert data into.
     * @param array  $data      An associative array containing column-value pairs.
     * @param array  $types     Types of the inserted data.
     *
     * @return int|string The number of affected rows.
     */
    public function insertPrefix($tableName, array $data, array $types = array())
    {
        $tableName = $this->prefix($tableName);
        return $this->insert($tableName, $data, $types);
    }

    /**
     * Executes an SQL UPDATE statement on a table.
     *
     * Adds prefix to the name of the table then passes to update().
     *
     * @param string $tableName  The name of the table to update.
     * @param array  $data       The data to update
     * @param array  $identifier The update criteria.
     * An associative array containing column-value pairs.
     * @param array  $types      Types of the merged $data and
     * $identifier arrays in that order.
     *
     * @return int|string The number of affected rows.
     */
    public function updatePrefix($tableName, array $data, array $identifier, array $types = array())
    {
        $tableName = $this->prefix($tableName);
        return $this->update($tableName, $data, $identifier, $types);
    }

    /**
     * Executes an SQL DELETE statement on a table.
     *
     * Adds prefix to the name of the table then passes to delete().
     *
     * @param string $tableName  The name of the table on which to delete.
     * @param array  $identifier The deletion criteria.
     * An associative array containing column-value pairs.
     *
     * @return int|string The number of affected rows.
     */
    public function deletePrefix($tableName, array $identifier)
    {
        $tableName = $this->prefix($tableName);
        return $this->delete($tableName, $identifier);
    }

    /**
     * Executes an SQL INSERT/UPDATE/DELETE query with the given parameters
     * and returns the number of affected rows.
     *
     * This method supports PDO binding types as well as DBAL mapping types.
     *
     * This over ridding process checks to make sure it is safe to do these.
     * If force is active then it will over ride the safe setting.
     *
     * @param string $sql    The SQL query.
     * @param array  $params The query parameters.
     * @param array  $types  The parameter types.
     *
     * @return int|string The number of affected rows.
     *
     * @internal PERF: Directly prepares a driver statement, not a wrapper.
     *
     * @todo build a better exception catching system.
     */
    public function executeStatement(string $sql, array $params = [], array $types = []): int|string
    {
        $events = \Xoops::getInstance()->events();
        if ($this->safe || $this->force) {
            if (!$this->transactionActive) {
                $this->force = false;
            };
            $events->triggerEvent('core.database.query.start');
            try {
                $result = parent::executeStatement($sql, $params, $types);
            } catch (\Exception $e) {
                $events->triggerEvent('core.exception', $e);
                $result = 0;
            }
            $events->triggerEvent('core.database.query.end');
        } else {
            //$events->triggerEvent('core.database.query.failure', (array('Not safe:')));
            return 0;
        }
        if ($result != 0) {
            //$events->triggerEvent('core.database.query.success', (array($sql)));
            return (int) $result;
        } else {
            //$events->triggerEvent('core.database.query.failure', (array($sql)));
            return 0;
        }
    }

    /**
     * Starts a transaction by suspending auto-commit mode.
     *
     * @return void
     */
    public function beginTransaction(): void
    {
        $this->transactionActive = true;
        parent::beginTransaction();
    }

    /**
     * Commits the current transaction.
     *
     * @return void
     */
    public function commit(): void
    {
        $this->transactionActive = false;
        $this->force = false;
        parent::commit();
    }

    /**
     * rolls back the current transaction.
     *
     * @return void
     */
    public function rollBack(): void
    {
        $this->transactionActive = false;
        $this->force = false;
        parent::rollBack();
    }

    /**
     * Perform a safe query - routes to executeQuery for SELECT or executeStatement for DML.
     *
     * This is a XOOPS-specific wrapper that provides safe query checking.
     * In DBAL 4.x, the generic query() method was removed from Connection.
     *
     * @param string $sql    The SQL to execute
     * @param array  $params The query parameters
     * @param array  $types  The parameter types
     *
     * @return Result|int|string|null Result for SELECT, affected rows for DML, null on failure
     */
    public function query(string $sql, array $params = [], array $types = [])
    {
        return $this->safeQuery($sql, $params, $types);
    }

    /**
     * Perform a safe query - routes to executeQuery for SELECT or executeStatement for DML.
     *
     * This is a XOOPS-specific wrapper that provides safe query checking.
     * In DBAL 4.x, the generic query() method was removed from Connection.
     *
     * @param string $sql    The SQL to execute
     * @param array  $params The query parameters
     * @param array  $types  The parameter types
     *
     * @return Result|int|string|null Result for SELECT, affected rows for DML, null on failure
     */
    public function safeQuery(string $sql, array $params = [], array $types = [])
    {
        $events = \Xoops::getInstance()->events();
        $trimmedSql = ltrim($sql);
        $isSelect = strtolower(substr($trimmedSql, 0, 6)) === 'select';

        if (!$this->safe && !$this->force) {
            if (!$isSelect) {
                return null;
            }
        }
        $this->force = false;
        $events->triggerEvent('core.database.query.start');
        try {
            if ($isSelect) {
                $result = parent::executeQuery($sql, $params, $types);
            } else {
                $result = parent::executeStatement($sql, $params, $types);
            }
        } catch (\Exception $e) {
            $events->triggerEvent('core.exception', $e);
            $result = null;
        }
        $events->triggerEvent('core.database.query.end');
        return $result ?: null;
    }

    /**
     * perform queries from SQL dump file in a batch
     *
     * @param string $file file path to an SQL dump file
     *
     * @return bool FALSE if failed reading SQL file or
     * TRUE if the file has been read and queries executed
     */
    public function queryFromFile($file)
    {
        if (false !== ($fp = fopen($file, 'r'))) {
            $sql_queries = trim(fread($fp, filesize($file)));
            \SqlUtility::splitMySqlFile($pieces, $sql_queries);
            foreach ($pieces as $query) {
                $prefixed_query = \SqlUtility::prefixQuery(trim($query), $this->prefix());
                if ($prefixed_query != false) {
                    $this->safeQuery($prefixed_query[0]);
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Backward-compatible errorInfo() for DBAL 4.x.
     *
     * In DBAL 4.x, errorInfo() was removed. Errors are thrown as exceptions.
     * This provides a fallback via the native PDO connection.
     *
     * @return array PDO errorInfo array or empty array
     */
    public function errorInfo(): array
    {
        try {
            $pdo = $this->getNativeConnection();
            if ($pdo instanceof \PDO) {
                return $pdo->errorInfo();
            }
        } catch (\Throwable $e) {
        }
        return ['', '', ''];
    }

    /**
     * Backward-compatible errorCode() for DBAL 4.x.
     *
     * In DBAL 4.x, errorCode() was removed. Errors are thrown as exceptions.
     * This provides a fallback via the native PDO connection.
     *
     * @return string|int|null SQLSTATE error code or null
     */
    public function errorCode(): string|int|null
    {
        try {
            $pdo = $this->getNativeConnection();
            if ($pdo instanceof \PDO) {
                return $pdo->errorCode();
            }
        } catch (\Throwable $e) {
        }
        return null;
    }

    /**
     * Create a new instance of a SQL query builder.
     *
     * @return QueryBuilder
     */
    public function createXoopsQueryBuilder()
    {
        return new QueryBuilder($this);
    }
}
