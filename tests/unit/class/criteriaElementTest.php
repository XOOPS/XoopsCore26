<?php
require_once(__DIR__.'/../init_new.php');

use Doctrine\DBAL\Query\QueryBuilder;

class CriteriaElementTestInstance extends CriteriaElement
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

class CriteriaElementTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = 'CriteriaElementTestInstance';

    public function test___construct()
    {
        $x = new $this->myclass();
        $this->assertInstanceOf('CriteriaElement', $x);
        $this->assertInstanceOf('\\Xoops\\Core\\Kernel\\CriteriaElement', $x);
    }
}
