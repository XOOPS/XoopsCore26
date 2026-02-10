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
 * A single criteria for database query
 *
 * @category  Xoops\Core\Kernel\Criteria
 * @package   Xoops\Core\Kernel
 * @author    Kazumi Ono <onokazu@xoops.org>
 * @author    Nathan Dial <ndial@trillion21.com>
 * @author    Taiwen Jiang <phppp@users.sourceforge.net>
 * @copyright 2000-2013 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      http://xoops.org
 * @since     2.0.0
 */
class Criteria extends CriteriaElement
{
    /**
     * @var string
     */
    public $prefix;

    /**
     * @var string
     */
    public $function;

    /**
     * @var string
     */
    public $column;

    /**
     * @var string
     */
    public $operator;

    /**
     * @var mixed
     */
    public $value;

    /**
     * Constructor
     *
     * @param string $column   column criteria applies to
     * @param string $value    value to compare to column
     * @param string $operator operator to apply to column
     * @param string $prefix   prefix to append to column
     * @param string $function sprintf string taking one string argument applied to column
     */
    public function __construct($column, $value = '', $operator = '=', $prefix = '', $function = '')
    {
        $this->prefix = $prefix;
        $this->function = $function;
        $this->column = $column;
        $this->value = $value;
        $this->operator = $operator;
    }

    /**
     * Make a sql condition string
     *
     * @return string
     */
    public function render()
    {
        $clause = (!empty($this->prefix) ? "{$this->prefix}." : "") . $this->column;
        if (!empty($this->function)) {
            $clause = sprintf($this->function, $clause);
        }
        if (in_array(strtoupper($this->operator), array('IS NULL', 'IS NOT NULL'))) {
            $clause .= ' ' . $this->operator;
        } else {
            if ('' === ($value = trim($this->value))) {
                return '';
            }
            if (!in_array(strtoupper($this->operator), array('IN', 'NOT IN'))) {
                if ((substr($value, 0, 1) !== '`') && (substr($value, -1) !== '`')) {
                    $value = "'{$value}'";
                } else {
                    if (!preg_match('/^[a-zA-Z0-9_\.\-`]*$/', $value)) {
                        $value = '``';
                    }
                }
            }
            $clause .= " {$this->operator} {$value}";
        }

        return $clause;
    }

    /**
     * Generate an LDAP filter from criteria
     *
     * @return string
     * @author Nathan Dial ndial@trillion21.com, improved by Pierre-Eric MENUET pemen@sourceforge.net
     */
    public function renderLdap()
    {
        $clause = '';
        if ($this->operator === '>') {
            $this->operator = '>=';
        }
        if ($this->operator === '<') {
            $this->operator = '<=';
        }

        if ($this->operator === '!=' || $this->operator === '<>') {
            $operator = '=';
            $clause = "(!(" . $this->column . $operator . $this->value . "))";
        } else {
            if ($this->operator === 'IN') {
                $newvalue = str_replace(array('(', ')'), '', $this->value);
                $tab = explode(',', $newvalue);
                foreach ($tab as $uid) {
                    $clause .= "({$this->column}={$uid})";
                }
                $clause = '(|' . $clause . ')';
            } else {
                $clause = "(" . $this->column . ' ' . $this->operator . ' ' . $this->value . ")";
            }
        }
        return $clause;
    }

    /**
     * Make a SQL "WHERE" clause
     *
     * @return string
     */
    public function renderWhere()
    {
        $cond = $this->render();
        return empty($cond) ? '' : "WHERE {$cond}";
    }

    /**
     * Render criteria as Doctrine QueryBuilder instructions
     *
     * @param QueryBuilder $qb        query builder instance
     * @param string       $whereMode how does this fit in the passed in QueryBuilder?
     *                                '' = as where,'and'= as andWhere, 'or' = as orWhere
     *
     * @return QueryBuilder query builder instance
     */
    public function renderQb(?QueryBuilder $qb = null, $whereMode = '')
    {
        if ($qb==null) { // initialize query builder if not passed in
            $qb = \Xoops::getInstance()->db()->createXoopsQueryBuilder();
            $whereMode = ''; // first entry in new instance must be where
        }
        $expr = $this->buildExpressionQb($qb);

        switch (strtolower($whereMode)) {
            case 'and':
                $qb->andWhere($expr);
                break;
            case 'or':
                $qb->orWhere($expr);
                break;
            case '':
                $qb->where($expr);
                break;
        }

        if ($this->limit!=0 || $this->start!=0) {
            $qb->setFirstResult($this->start)
                ->setMaxResults($this->limit);
        }

        if (!empty($this->groupBy)) {
            $qb->groupBy($this->groupBy);
        }

        if (!empty($this->sort)) {
            $qb->orderBy($this->sort, $this->order);
        }

        return $qb;
    }

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
    public function buildExpressionQb(QueryBuilder $qb)
    {
        $eb = $qb->expr();

        $column = (empty($this->prefix) ? "" : $this->prefix.'.') . $this->column;

        // this should be done using portability functions
        if (!empty($this->function)) {
            $column = sprintf($this->function, $column);
        }

        $value=trim($this->value);

        $operator = strtolower($this->operator);
        $expr = '';

        // handle special case of value
        if (in_array($operator, array('is null', 'is not null', 'in', 'not in'))) {
            switch ($operator) {
                case 'is null':
                    $expr = $eb->isNull($column);
                    break;
                case 'is not null':
                    $expr = $eb->isNotNull($column);
                    break;
                case 'in':
                    if (!empty($value) && $value!=='()') {
                        $expr = $column . ' IN ' . $value;
                    } else {
                        // odd case of a null set - this won't match anything
                        $expr = $eb->neq($column, $column);
                    }
                    break;
                case 'not in':
                    if (!empty($value) && $value!=='()') {
                        $expr = $column . ' NOT IN ' . $value;
                    }
                    break;
            }
        } elseif (!empty($column)) { // no value is a nop (bug: this should be a valid value)
            $columnValue = $qb->createNamedParameter($value);
            switch ($operator) {
                case '=':
                case 'eq':
                    $expr = $eb->eq($column, $columnValue);
                    break;
                case '!=':
                case '<>':
                case 'neq':
                    $expr = $eb->neq($column, $columnValue);
                    break;
                case '<':
                case 'lt':
                    $expr = $eb->lt($column, $columnValue);
                    break;
                case '<=':
                case 'lte':
                    $expr = $eb->lte($column, $columnValue);
                    break;
                case '>':
                case 'gt':
                    $expr = $eb->gt($column, $columnValue);
                    break;
                case '>=':
                case 'gte':
                    $expr = $eb->gte($column, $columnValue);
                    break;
                case 'like':
                    $expr = $eb->like($column, $columnValue);
                    break;
                case 'not like':
                    $expr = $eb->notLike($column, $columnValue);
                    break;
                default:
                    $expr = $eb->comparison($column, strtoupper($operator), $columnValue);
                    break;
            }
        } else {
            $expr = '(1)';
        }
        return $expr;
    }
}
