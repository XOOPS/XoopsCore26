<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

namespace Xoops\Core\Kernel;

use Doctrine\DBAL\Query\QueryBuilder;

/**
 * A criteria (grammar?) for a database query.
 *
 * This abstract base class should never be instantiated directly.
 *
 * @category  Xoops\Core\Kernel\CriteriaElement
 * @package   Xoops\Core\Kernel
 * @author    Kazumi Ono <onokazu@xoops.org>
 * @author    Nathan Dial <ndial@trillion21.com>
 * @author    Taiwen Jiang <phppp@users.sourceforge.net>
 * @copyright 2000-2013 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      http://xoops.org
 * @since     2.0.0
 */
abstract class CriteriaElement
{
    /**
     * Sort order
     *
     * @var string
     */
    protected $order = 'ASC';

    /**
     * @var string
     */
    protected $sort = '';

    /**
     * Number of records to retrieve
     *
     * @var int
     */
    protected $limit = 0;

    /**
     * Offset of first record
     *
     * @var int
     */
    protected $start = 0;

    /**
     * @var string
     */
    protected $groupBy = '';


    /**
     * Render the criteria element
     *
     * @return string
     */
    abstract public function render();

    /**
     * Make the criteria into a SQL "WHERE" clause
     *
     * @return string
     */
    abstract public function renderWhere();

    /**
     * Generate an LDAP filter from criteria
     *
     * @return string
     */
    abstract public function renderLdap();

    /**
     * Render as Doctrine QueryBuilder instructions
     *
     * @param QueryBuilder $qb        query builder instance
     * @param string       $whereMode how does this fit in the passed in QueryBuilder?
     *                                '' = as where,'and'= as andWhere, 'or' = as orWhere
     *
     * @return QueryBuilder query builder instance
     */
    abstract public function renderQb(?QueryBuilder $qb = null, $whereMode = '');

    /**
     * Build an expression to be included in a Doctrine QueryBuilder instance.
     *
     * This method will build an expression, adding any parameters to the query,
     * but the caller is responsible for adding the expression to the query, for
     * example as where() parameter. This allows the caller to handle all context,
     * such as parenthetical groupings.
     *
     * @param QueryBuilder $qb query builder instance
     *
     * @return string expression
     */
    abstract public function buildExpressionQb(QueryBuilder $qb);

    /**
     * set sort column
     *
     * @param string $sort sort column
     *
     * @return void
     */
    public function setSort($sort)
    {
        $this->sort = $sort;
    }

    /**
     * get sort column
     *
     * @return string sort column
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * set sort order
     *
     * @param string $order sort order ASC or DESC
     *
     * @return void
     */
    public function setOrder($order)
    {
        if (is_string($order)) {
            $order = strtoupper($order);
            if (in_array($order, array('ASC', 'DESC'))) {
                $this->order = $order;
            }
        }
    }

    /**
     * get sort order
     *
     * @return string sort order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * set row limit
     *
     * @param int $limit row limit
     *
     * @return void
     */
    public function setLimit($limit = 0)
    {
        $this->limit = (int)($limit);
    }

    /**
     * get row limit
     *
     * @return int row limit
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * set first row offset
     *
     * @param int $start offset of first row
     *
     * @return void
     */
    public function setStart($start = 0)
    {
        $this->start = (int)($start);
    }

    /**
     * get first row offset
     *
     * @return int start row offset
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * set group by
     *
     * @param string $group group by
     *
     * @return void
     */
    public function setGroupBy($group)
    {
        $this->groupBy = $group;
    }

    /**
     * get group by
     *
     * @return string group by
     */
    public function getGroupBy()
    {
        return isset($this->groupBy) ? $this->groupBy : "";
    }
}
