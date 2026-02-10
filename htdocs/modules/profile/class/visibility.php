<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

use Xoops\Core\Database\Connection;
use Xoops\Core\Kernel\Dtype;
use Xoops\Core\Kernel\XoopsObject;
use Xoops\Core\Kernel\XoopsPersistableObjectHandler;
use Xoops\Core\Kernel\CriteriaElement;

/**
 * Extended User Profile
 *
 * @author          Jan Pedersen
 * @author          Taiwen Jiang <phppp@users.sourceforge.net>
 * @copyright 2000-2019 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 */

class ProfileVisibility extends XoopsObject
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->initVar('field_id', Dtype::TYPE_INTEGER);
        $this->initVar('user_group', Dtype::TYPE_INTEGER);
        $this->initVar('profile_group', Dtype::TYPE_INTEGER);
    }
}

class ProfileVisibilityHandler extends XoopsPersistableObjectHandler
{
    /**
     * @param null|Connection $db database
     */
    public function __construct(?Connection $db = null)
    {
        parent::__construct($db, 'profile_visibility', 'profilevisibility', 'field_id');
    }

    /**
     * Get fields visible to the $user_groups on a $profile_groups profile
     *
     * @param array $profile_groups groups of the user to be accessed
     * @param array $user_groups    groups of the visitor, default as $xoops->user
     *
     * @return array
     */
    public function getVisibleFields($profile_groups, $user_groups = null)
    {
        $profile_groups[] = 0;
        array_walk($profile_groups, 'intval');
        $user_groups[] = 0;
        array_walk($user_groups, 'intval');

        $qb = $this->db2->createXoopsQueryBuilder();
        $eb = $qb->expr();
        $sql = $qb->select('t1.field_id')
            ->from($this->table, 't1')
            ->where($eb->in('t1.profile_group', $profile_groups))
            ->andWhere($eb->in('t1.user_group', $user_groups));

        $result = $sql->execute();
        $field_ids = array();
        while (list($field_id) = $result->fetchNumeric()) {
            $field_ids[] = $field_ids;
        }

        return $field_ids;
    }

    /**
     * get all rows matching a condition
     *
     * @param  CriteriaElement $criteria  {@link CriteriaElement} to match
     *
     * @return array of row arrays, indexed by field_id
     */
    public function getAllByFieldId(?CriteriaElement $criteria = null)
    {
        $rawRows = $this->getAll($criteria, null, false, false);

        usort($rawRows, array($this, 'visibilitySort'));

        $rows = array();
        foreach ($rawRows as $rawRow) {
            $rows[$rawRow['field_id']][] = $rawRow;
        }

        return $rows;
    }

    /**
     * compare two arrays, each a row from profile_visibility
     * The comparison is on three columns, 'field_id', 'user_group', 'profile_group' considered in that
     * order for comparison
     *
     * @param array $a associative array with 3 numeric entries 'field_id', 'user_group', 'profile_group'
     * @param array $b associative array with 3 numeric entries 'field_id', 'user_group', 'profile_group'
     *
     * @return int integer less that zero if $a is less than $b
     *              integer zero if $a and $b are equal
     *              integer greater than zero if $a is greater than $b
     */
    protected function visibilitySort($a, $b)
    {
        $fieldDiff = $a['field_id'] - $b['field_id'];
        $userDiff  = $a['user_group'] - $b['user_group'];
        $profDiff  = $a['profile_group'] - $b['profile_group'];
        if (0 != $fieldDiff) {
            return $fieldDiff;
        } elseif (0 !== $userDiff) {
            return $userDiff;
        } else {
            return $profDiff;
        }
    }
}
