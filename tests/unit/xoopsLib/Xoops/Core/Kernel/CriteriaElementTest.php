<?php
require_once(__DIR__.'/../../../../init_new.php');

use Doctrine\DBAL\Query\QueryBuilder;

class Kernel_CriteriaElementTestInstance extends Xoops\Core\Kernel\CriteriaElement
{
    public function render()
    {
    }
    public function renderWhere()
    {
    }
    public function renderLdap()
    {
    }
    public function renderQb(?QueryBuilder $qb = null, $whereMode = '')
    {
    }
    public function buildExpressionQb(QueryBuilder $qb)
    {
    }
}

class Kernel_CriteriaElementTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = 'Kernel_CriteriaElementTestInstance';

    public function test___construct()
    {
        $instance = new $this->myclass();
        $this->assertInstanceOf($this->myclass, $instance);
    }

    public function test_setSort()
    {
        $instance = new $this->myclass();
        $this->assertInstanceOf($this->myclass, $instance);
        $sort = 'sort';
        $instance->setSort($sort);
        $this->assertSame($sort, $instance->getSort());
    }

    public function test_setOrder()
    {
        $instance = new $this->myclass();
        $this->assertInstanceOf($this->myclass, $instance);
        $save = $instance->getOrder();
        $order = 1;
        $instance->setOrder($order);
        $this->assertSame($save, $instance->getOrder());

        $save = $instance->getOrder();
        $order = 'asc';
        $instance->setOrder($order);
        $this->assertSame(strtoupper($order), $instance->getOrder());

        $save = $instance->getOrder();
        $order = 'desc';
        $instance->setOrder($order);
        $this->assertSame(strtoupper($order), $instance->getOrder());

        $save = $instance->getOrder();
        $order = 'dummy';
        $instance->setOrder($order);
        $this->assertSame($save, $instance->getOrder());
    }

    public function test_setLimit()
    {
        $instance = new $this->myclass();
        $this->assertInstanceOf($this->myclass, $instance);
        $save = $instance->getLimit();
        $limit = 71;
        $instance->setLimit($limit);
        $this->assertSame($limit, $instance->getLimit());
    }

    public function test_setStart()
    {
        $instance = new $this->myclass();
        $this->assertInstanceOf($this->myclass, $instance);
        $save = $instance->getStart();
        $start = 71;
        $instance->setStart($start);
        $this->assertSame($start, $instance->getStart());
    }

    public function test_setGroupby()
    {
        $instance = new $this->myclass();
        $this->assertInstanceOf($this->myclass, $instance);
        $save = $instance->getGroupby();
        $groupby = 'groupby';
        $instance->setGroupby($groupby);
        $this->assertSame($groupby, $instance->getGroupby());
    }
}
