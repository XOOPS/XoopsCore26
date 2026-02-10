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
 * Object render handler class.
 *
 * @category  Xoops\Core\Kernel\Model\Read
 * @package   Xoops\Core\Kernel
 * @author    Taiwen Jiang <phppp@users.sourceforge.net>
 * @copyright 2000-2019 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 */
class Read extends XoopsModelAbstract
{
    /**
     * get all objects matching a condition
     *
     * @param CriteriaElement|null $criteria  criteria to match
     * @param array                $fields    variables to fetch
     * @param bool                 $asObject  flag indicating as object, otherwise as array
     * @param bool                 $id_as_key use the ID as key for the array
     *
     * @return array of objects/array as specified in $asObject
     */
    public function getAll(?CriteriaElement $criteria = null, $fields = null, $asObject = true, $id_as_key = true)
    {
        $qb = $this->handler->db2->createXoopsQueryBuilder();

        if (is_array($fields) && count($fields) > 0) {
            if (!in_array($this->handler->keyName, $fields)) {
                $fields[] = $this->handler->keyName;
            }
            $first=true;
            foreach ($fields as $field) {
                if ($first) {
                    $first=false;
                    $qb->select($field);
                } else {
                    $qb->addSelect($field);
                }
            }
        } else {
            $qb->select('*');
        }
        $qb->from($this->handler->table, null);
        if (isset($criteria)) {
            $qb = $criteria->renderQb($qb);
        }

        $ret = array();
        $result = $qb->execute();
        if (!$result) {
            return $ret;
        }
        if ($asObject) {
            while ($myrow = $result->fetchAssociative()) {
                $object = $this->handler->create(false);
                $object->assignVars($myrow);
                if ($id_as_key) {
                    $ret[$myrow[$this->handler->keyName]] = $object;
                } else {
                    $ret[] = $object;
                }
                unset($object);
            }
        } else {
            $object = $this->handler->create(false);
            while ($myrow = $result->fetchAssociative()) {
                $object->assignVars($myrow);
                if ($id_as_key) {
                    $ret[$myrow[$this->handler->keyName]] = $object->getValues();
                } else {
                    $ret[] = $object->getValues();
                }
            }
            unset($object);
        }
        return $ret;
    }

    /**
     * retrieve objects from the database
     *
     * For performance consideration, getAll() is recommended
     *
     * @param CriteriaElement|null $criteria  criteria to match
     * @param bool                 $id_as_key use the ID as key for the array
     * @param bool                 $as_object return an array of objects?
     *
     * @return array
     */
    public function getObjects(?CriteriaElement $criteria = null, $id_as_key = false, $as_object = true)
    {
        $objects = $this->getAll($criteria, null, $as_object, $id_as_key);
        return $objects;
    }

    /**
     * Retrieve a list of objects data
     *
     * @param CriteriaElement|null $criteria criteria to match
     * @param int                  $limit    Max number of objects to fetch
     * @param int                  $start    Which record to start at
     *
     * @return array
     */
    public function getList(?CriteriaElement $criteria = null, $limit = 0, $start = 0)
    {
        //$qb = Xoops::getInstance()->db()->createXoopsQueryBuilder();
        $qb = $this->handler->db2->createXoopsQueryBuilder();

        $ret = array();

        $qb->select($this->handler->keyName);
        if (!empty($this->handler->identifierName)) {
            $qb->addSelect($this->handler->identifierName);
        }
        $qb->from($this->handler->table, null);
        if ($limit!=0 || $start!=0) {
            $qb->setFirstResult($start)
                ->setMaxResults($limit);
        }
        $qb->orderBy($this->handler->keyName); // any criteria order will override
        if (!empty($criteria)) {
            $qb = $criteria->renderQb($qb);
        }
        $result = $qb->execute();
        if (!$result) {
            return $ret;
        }

        $myts = \Xoops\Core\Text\Sanitizer::getInstance();
        while ($myrow = $result->fetchAssociative()) {
            // identifiers should be textboxes, so sanitize them like that
            $ret[$myrow[$this->handler->keyName]] = empty($this->handler->identifierName) ? 1
                : $myts->htmlSpecialChars($myrow[$this->handler->identifierName]);
        }
        return $ret;
    }

    /**
     * get IDs of objects matching a condition
     *
     * @param CriteriaElement|null $criteria criteria to match
     *
     * @return array of object IDs
     */
    public function getIds(?CriteriaElement $criteria = null)
    {
        $qb = $this->handler->db2->createXoopsQueryBuilder();

        $ret = array();

        $qb->select($this->handler->keyName);
        $qb->from($this->handler->table, null);
        if (!empty($criteria)) {
            $qb = $criteria->renderQb($qb);
        }
        $result = $qb->execute();
        if (!$result) {
            return $ret;
        }

        while ($myrow = $result->fetchAssociative()) {
            $ret[] = $myrow[$this->handler->keyName];
        }
        return $ret;
    }

    /**
     * getRandomObject - return a randomly selected object
     *
     * @param CriteriaElement|null $criteria criteria to match
     *
     * @return \Xoops\Core\Kernel\XoopsObject|null object or null if no matching object found
     */
    public function getRandomObject(?CriteriaElement $criteria = null)
    {
        $qb = $this->handler->db2->createXoopsQueryBuilder();
        $qb ->select('COUNT(*)')
            ->from($this->handler->table, null);
        if (null !== $criteria) {
            $qb = $criteria->renderQb($qb);
        }
        $result = $qb->execute();
        $count = $result->fetchOne();

        $offset = mt_rand(0, $count - 1);

        $qb = $this->handler->db2->createXoopsQueryBuilder();
        $qb ->select($this->handler->keyName)
            ->from($this->handler->table, null);
        if (null !== $criteria) {
            $qb = $criteria->renderQb($qb);
        }
        $qb ->setFirstResult($offset)
            ->setMaxResults(1);

        $result = $qb->execute();
        $randomKey = $result->fetchOne();

        return $this->handler->get($randomKey);
    }
}
