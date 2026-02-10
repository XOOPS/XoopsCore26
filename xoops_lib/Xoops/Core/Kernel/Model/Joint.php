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
 * Object joint handler class.
 *
 * @category  Xoops\Core\Kernel\Model\Joint
 * @package   Xoops\Core\Kernel
 * @author    Taiwen Jiang <phppp@users.sourceforge.net>
 * @copyright 2000-2019 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 *
 * Usage of methods provided by XoopsModelJoint:
 *
 * Step #1: set linked table and joint fields through XoopsPersistableObjectHandler:
 *      $handler->table_link = $handler->db2->prefix("the_linked_table");
 *          full name of the linked table that is used for the query
 *      $handler->field_link = "the_linked_field";
 *          name of field in linked table that will be used to link the linked table
 *          with current table
 *      $handler->field_object = "the_object_field";
 *          name of field in current table that will be used to link the linked table
 *          with current table; linked field name will be used if the field name is
 *          not set
 * Step #2: fetch data
 */
class Joint extends XoopsModelAbstract
{
    /**
     * Validate information for the linkage
     *
     * @return bool
     */
    private function validateLinks()
    {
        if (empty($this->handler->table_link) || empty($this->handler->field_link)) {
            trigger_error("The linked table is not set yet.", E_USER_WARNING);
            return false;
        }
        if (empty($this->handler->field_object)) {
            $this->handler->field_object = $this->handler->field_link;
        }
        return true;
    }

    /**
     * get a list of objects matching a condition joint with another related object
     *
     * @param CriteriaElement|null $criteria     criteria to match
     * @param array                $fields       variables to fetch
     * @param bool                 $asObject     flag indicating as object, otherwise as array
     * @param string               $field_link   field of linked object for JOIN;
     *                                            deprecated, for backward compatibility only
     * @param string               $field_object field of current object for JOIN;
     *                                            deprecated, for backward compatibility only
     *
     * @return false|array array as requested by $asObject
     */
    public function getByLink(
        ?CriteriaElement $criteria = null,
        $fields = null,
        $asObject = true,
        $field_link = null,
        $field_object = null
    ) {
        if (!empty($field_link)) {
            $this->handler->field_link = $field_link;
        }
        if (!empty($field_object)) {
            $this->handler->field_object = $field_object;
        }
        if (!$this->validateLinks()) {
            return false;
        }

        $qb = $this->handler->db2->createXoopsQueryBuilder();
        if (is_array($fields) && count($fields)) {
            if (!in_array("o." . $this->handler->keyName, $fields)) {
                $fields[] = "o." . $this->handler->keyName;
            }
            $first = true;
            foreach ($fields as $field) {
                if ($first) {
                    $first = false;
                    $qb->select($field);
                } else {
                    $qb->addSelect($field);
                }
            }
        } else {
            $qb ->select('o.*')
                ->addSelect('l.*');
        }
        $qb ->from($this->handler->table, 'o')
            ->leftJoin(
                'o',
                $this->handler->table_link,
                'l',
                "o.{$this->handler->field_object} = l.{$this->handler->field_link}"
            );
        if (isset($criteria) && ($criteria instanceof CriteriaElement)) {
            $qb = $criteria->renderQb($qb);
        }
        $result = $qb->execute();
        $ret = array();
        if ($asObject) {
            while ($myrow = $result->fetchAssociative()) {
                $object = $this->handler->create(false);
                $object->assignVars($myrow);
                $ret[$myrow[$this->handler->keyName]] = $object;
                unset($object);
            }
        } else {
            $object = $this->handler->create(false);
            while ($myrow = $result->fetchAssociative()) {
                $object->assignVars($myrow);
                $ret[$myrow[$this->handler->keyName]] = $object->getValues();
            }
            unset($object);
        }
        return $ret;
    }

    /**
     * Count of objects matching a condition
     *
     * @param CriteriaElement|null $criteria criteria to match
     *
     * @return false|int count of objects
     */
    public function getCountByLink(?CriteriaElement $criteria = null)
    {
        if (!$this->validateLinks()) {
            return false;
        }

        $qb = $this->handler->db2->createXoopsQueryBuilder();

        $qb ->select("COUNT(DISTINCT o.{$this->handler->keyName})")
            ->from($this->handler->table, 'o')
            ->leftJoin(
                'o',
                $this->handler->table_link,
                'l',
                "o.{$this->handler->field_object} = l.{$this->handler->field_link}"
            );

        if (isset($criteria) && ($criteria instanceof CriteriaElement)) {
            $criteria->renderQb($qb);
        }

        $result = $qb->execute();
        return $result->fetchOne();
    }

    /**
     * array of count of objects matching a condition of, groupby linked object keyname
     *
     * @param CriteriaElement $criteria criteria to match
     *
     * @return false|int count of objects
     */
    public function getCountsByLink(?CriteriaElement $criteria = null)
    {
        if (!$this->validateLinks()) {
            return false;
        }

        $qb = $this->handler->db2->createXoopsQueryBuilder();

        $qb ->select("l.{$this->handler->field_link}")
            ->addSelect('COUNT(*)')
            ->from($this->handler->table, 'o')
            ->leftJoin(
                'o',
                $this->handler->table_link,
                'l',
                "o.{$this->handler->field_object} = l.{$this->handler->field_link}"
            );

        if (isset($criteria) && ($criteria instanceof CriteriaElement)) {
            $criteria->renderQb($qb);
        }

        $qb ->groupBy("l.{$this->handler->field_link}");

        $result = $qb->execute();

        $ret = array();
        while (list($id, $count) = $result->fetchNumeric()) {
            $ret[$id] = $count;
        }
        return $ret;
    }

    /**
     * update objects matching a condition against linked objects
     *
     * @param array                $data     array of key => value
     * @param CriteriaElement|null $criteria criteria to match
     *
     * @return false|int count of objects
     *
     * @todo UPDATE ... LEFT JOIN is not portable
     * Note Alain91 : multi tables update is not allowed in Doctrine
     */
    public function updateByLink(array $data, ?CriteriaElement $criteria = null)
    {
        if (!$this->validateLinks()) {
            return false;
        }
        if (empty($data) || empty($criteria)) { // avoid update all records
            return false;
        }

        $set = array();
        foreach ($data as $key => $val) {
            $set[] = "o.{$key}=" . $this->handler->db2->quote($val);
        }
        $sql = " UPDATE {$this->handler->table} AS o" . " SET " . implode(", ", $set)
            . " LEFT JOIN {$this->handler->table_link} AS l "
            . "ON o.{$this->handler->field_object} = l.{$this->handler->field_link}";
        if (isset($criteria) && ($criteria instanceof CriteriaElement)) {
            $sql .= " " . $criteria->renderWhere();
        }

        return $this->handler->db2->executeStatement($sql);
    }

    /**
     * Delete objects matching a condition against linked objects
     *
     * @param CriteriaElement|null $criteria criteria to match
     *
     * @return false|int count of objects
     *
     * @todo DELETE ... LEFT JOIN is not portable
     * Note Alain91 : multi tables delete is not allowed in Doctrine
     */
    public function deleteByLink(?CriteriaElement $criteria = null)
    {
        if (!$this->validateLinks()) {
            return false;
        }
        if (empty($criteria)) { //avoid delete all records
            return false;
        }

        $sql = "DELETE FROM {$this->handler->table} AS o "
            . "LEFT JOIN {$this->handler->table_link} AS l "
            . "ON o.{$this->handler->field_object} = l.{$this->handler->field_link}";
        if (isset($criteria) && ($criteria instanceof CriteriaElement)) {
            $sql .= " " . $criteria->renderWhere();
        }

        return $this->handler->db2->executeStatement($sql);
    }
}
