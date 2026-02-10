<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

namespace Xoops\Core\Kernel\Model;

use Xoops\Core\Kernel\CriteriaElement;
use Xoops\Core\Kernel\XoopsModelAbstract;

/**
 * Object stats handler class.
 *
 * @category  Xoops\Core\Kernel\Model\Stats
 * @package   Xoops\Core\Kernel
 * @author    Taiwen Jiang <phppp@users.sourceforge.net>
 * @copyright 2000-2019 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 */
class Stats extends XoopsModelAbstract
{
    /**
     * count objects matching a condition
     *
     * @param CriteriaElement|null $criteria criteria to match
     *
     * @return int count of objects
     */
    public function getCount(?CriteriaElement $criteria = null)
    {
        $qb = \Xoops::getInstance()->db()->createXoopsQueryBuilder();

        $groupBy = false;
        if (isset($criteria) && ($criteria instanceof CriteriaElement)) {
            $temp = $criteria->getGroupBy();
            if (!empty($temp)) {
                $qb->select($temp);
                $groupBy = true;
            }
        }
        if (!$groupBy) {
            $qb->select('COUNT(*)');
        } else {
            $qb->addSelect('COUNT(*)');
        }

        $qb->from($this->handler->table, null);
        if (isset($criteria) && ($criteria instanceof CriteriaElement)) {
            $qb = $criteria->renderQb($qb);
        }
        try {
            $result = $qb->execute();
            if (!$result) {
                return 0;
            }
        } catch (\Exception $e) {
            \Xoops::getInstance()->events()->triggerEvent('core.exception', $e);
            return 0;
        }

        if ($groupBy == false) {
            list ($count) = $result->fetchNumeric();
            return $count;
        } else {
            $ret = array();
            while (list ($id, $count) = $result->fetchNumeric()) {
                $ret[$id] = $count;
            }
            return $ret;
        }
    }

    /**
     * get counts matching a condition
     *
     * @param CriteriaElement|null $criteria criteria to match
     *
     * @return array of counts
     */
    public function getCounts(?CriteriaElement $criteria = null)
    {
        $qb = \Xoops::getInstance()->db()->createXoopsQueryBuilder();

        $ret = array();
        $limit = null;
        $start = null;
        $groupby_key = $this->handler->keyName;
        if (isset($criteria) && ($criteria instanceof CriteriaElement)) {
            if ($groupBy = $criteria->getGroupBy()) {
                $groupby_key = $groupBy;
            }
        }
        $qb->select($groupby_key)
            ->addSelect('COUNT(*)')
            ->from($this->handler->table, null)
            ->groupBy($groupby_key);

        if (isset($criteria) && ($criteria instanceof CriteriaElement)) {
            $qb = $criteria->renderQb($qb);
        }
        $result = $qb->execute();
        if (!$result) {
            return $ret;
        }
        while (list ($id, $count) = $result->fetchNumeric()) {
            $ret[$id] = $count;
        }
        return $ret;
    }
}
