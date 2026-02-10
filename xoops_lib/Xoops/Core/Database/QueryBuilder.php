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

use Doctrine\DBAL\Query\QueryType;
use Xoops\Core\Database\Connection;

/**
 * Connection wrapper for Doctrine DBAL Connection
 *
 * PHP version 5.3
 *
 * @category  Xoops\Core\Database\QueryBuilder
 * @package   QueryBuilder
 * @author    readheadedrod <redheadedrod@hotmail.com>
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2013-2015 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @version   Release: 2.6.0
 * @link      http://xoops.org
 * @since     2.6.0
 */
class QueryBuilder extends \Doctrine\DBAL\Query\QueryBuilder
{

    /**
     * @var Connection DBAL Connection
     */
    private $connection = null;

    /**
     * __construct
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        parent::__construct($connection);
    }

    /**
     * Turns the query being built into a bulk delete query that ranges over
     * a certain table.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->delete('users', 'u')
     *         ->where('u.id = :user_id');
     *         ->setParameter('user_id', 1);
     * </code>
     *
     * @param string $delete The table whose rows are subject to the deletion.
     * Adds table prefix to table.
     * @param string $alias  The table alias used in the constructed query.
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function deletePrefix($delete = null, $alias = null)
    {
        $delete = $this->connection->prefix($delete);
        return $this->delete($delete, $alias);
    }

    /**
     * Turns the query being built into a bulk update query that ranges over
     * a certain table
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->update('users', 'u')
     *         ->set('u.password', md5('password'))
     *         ->where('u.id = ?');
     * </code>
     *
     * @param string $update The table whose rows are subject to the update.
     * Adds table prefix to table.
     * @param string $alias  The table alias used in the constructed query.
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function updatePrefix($update = null, $alias = null)
    {
        $update = $this->connection->prefix($update);
        return $this->update($update, $alias);
    }

    /**
     * Turns the query being built into an insert query that inserts into
     * a certain table
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->insert('users')
     *         ->values(
     *             array(
     *                 'name' => '?',
     *                 'password' => '?'
     *             )
     *         );
     * </code>
     *
     * @param string $insert The table into which the rows should be inserted.
     *                       Adds table prefix to table.
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function insertPrefix($insert = null)
    {
        $insert = $this->connection->prefix($insert);
        return $this->insert($insert);
    }

    /**
     * Create and add a query root corresponding to the table identified by the
     * given alias, forming a cartesian product with any existing query roots.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.id')
     *         ->from('users', 'u')
     * </code>
     *
     * @param string      $from  The table. Adds table prefix to table.
     * @param string|null $alias The alias of the table.
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function fromPrefix($from, $alias = null)
    {
        $from = $this->connection->prefix($from);
        return $this->from($from, $alias);
    }

    /**
     * Creates and adds a join to the query.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->join('u', 'phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $fromAlias The alias that points to a from clause
     * @param string $join      The table name to join. Adds table prefix to table.
     * @param string $alias     The alias of the join table
     * @param string $condition The condition for the join
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function joinPrefix($fromAlias, $join, $alias, $condition = null)
    {
        $join = $this->connection->prefix($join);
        return $this->join($fromAlias, $join, $alias, $condition);
    }


    /**
     * Creates and adds a join to the query.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->innerJoin('u', 'phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $fromAlias The alias that points to a from clause
     * @param string $join      The table name to join. Adds table prefix to table.
     * @param string $alias     The alias of the join table
     * @param string $condition The condition for the join
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function innerJoinPrefix($fromAlias, $join, $alias, $condition = null)
    {
        $join = $this->connection->prefix($join);
        return $this->innerJoin($fromAlias, $join, $alias, $condition);
    }

    /**
     * Creates and adds a left join to the query.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->leftJoin('u', 'phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $fromAlias The alias that points to a from clause
     * @param string $join      The table name to join. Adds table prefix to table.
     * @param string $alias     The alias of the join table
     * @param string $condition The condition for the join
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function leftJoinPrefix($fromAlias, $join, $alias, $condition = null)
    {
        $join = $this->connection->prefix($join);
        return $this->leftJoin($fromAlias, $join, $alias, $condition);
    }

    /**
     * Creates and adds a right join to the query.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->rightJoin('u', 'phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $fromAlias The alias that points to a from clause
     * @param string $join      The table name to join. Adds table prefix to table.
     * @param string $alias     The alias of the join table
     * @param string $condition The condition for the join
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function rightJoinPrefix($fromAlias, $join, $alias, $condition = null)
    {
        $join = $this->connection->prefix($join);
        return $this->rightJoin($fromAlias, $join, $alias, $condition);
    }

    /**
     * Backward-compatible resetQueryParts() for DBAL 4.x.
     *
     * In DBAL 3.x, QueryBuilder had a resetQueryParts() method to clear all
     * query state so the builder could be reused for a different query.
     * DBAL 4.x removed this method (QueryBuilder is intended to be single-use).
     * This restores that capability using reflection to reset the parent's
     * private properties to their constructor defaults.
     *
     * @return $this
     */
    public function resetQueryParts(): self
    {
        static $reflectionProps = null;

        if ($reflectionProps === null) {
            $parentClass = new \ReflectionClass(\Doctrine\DBAL\Query\QueryBuilder::class);
            $defaults = [
                'sql'                     => null,
                'params'                  => [],
                'types'                   => [],
                'type'                    => QueryType::SELECT,
                'firstResult'             => 0,
                'maxResults'              => null,
                'boundCounter'            => 0,
                'select'                  => [],
                'distinct'                => false,
                'from'                    => [],
                'table'                   => null,
                'join'                    => [],
                'set'                     => [],
                'where'                   => null,
                'groupBy'                 => [],
                'having'                  => null,
                'orderBy'                 => [],
                'forUpdate'               => null,
                'values'                  => [],
                'unionParts'              => [],
                'commonTableExpressions'  => [],
                'resultCacheProfile'      => null,
            ];
            $reflectionProps = [];
            foreach ($defaults as $propName => $defaultValue) {
                if ($parentClass->hasProperty($propName)) {
                    $prop = $parentClass->getProperty($propName);
                    $reflectionProps[] = [$prop, $defaultValue];
                }
            }
        }

        foreach ($reflectionProps as [$prop, $defaultValue]) {
            $prop->setValue($this, $defaultValue);
        }

        return $this;
    }

    /**
     * Backward-compatible execute() method for DBAL 4.x.
     *
     * In DBAL 3.x, QueryBuilder had an execute() method. In DBAL 4.x it was
     * removed in favor of executeQuery() and executeStatement(). This method
     * detects the query type from the SQL and dispatches accordingly.
     *
     * @return \Doctrine\DBAL\Result|int|string
     */
    public function execute()
    {
        $sql = ltrim($this->getSQL());
        if (stripos($sql, 'SELECT') === 0 || stripos($sql, 'WITH') === 0) {
            return $this->executeQuery();
        }
        return $this->executeStatement();
    }
}
