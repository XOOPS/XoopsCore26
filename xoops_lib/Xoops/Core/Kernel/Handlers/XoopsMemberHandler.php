<?php
/**
 * XOOPS Kernel Class
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         kernel
 * @since           2.0.0
 * @author          Kazumi Ono (AKA onokazu) http://www.myweb.ne.jp/, http://jp.xoops.org/
 * @version         $Id$
 */

namespace Xoops\Core\Kernel\Handlers;

use Xoops\Core\Database\Connection;
use Xoops\Core\FixedGroups;
use Xoops\Core\Kernel\Criteria;
use Xoops\Core\Kernel\CriteriaCompo;
use Xoops\Core\Kernel\CriteriaElement;

/**
 * XOOPS member handler class.
 * This class provides simple interface (a facade class) for handling groups/users/
 * membership data.
 *
 * @category  Xoops\Core\Kernel\Handlers\XoopsMemberHandler
 * @package   Xoops\Core\Kernel
 * @author    Kazumi Ono <onokazu@xoops.org>
 * @copyright 2000-2015 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      http://xoops.org
 */
class XoopsMemberHandler
{

    /**
     * @var XoopsGroupPermHandler group handler(DAO) class
     */
    private $groupHandler;

    /**
     * @var XoopsUserHandler user handler(DAO) class
     */
    private $userHandler;

    /**
     * @var XoopsMembershipHandler membership handler(DAO) class
     */
    private $membershipHandler;

    /**
     * holds temporary user objects
     */
    private $membersWorkingList = array();

    /**
     * Constructor
     *
     * @param Connection|null $db database connection
     */
    public function __construct(?Connection $db = null)
    {
        $this->groupHandler = \Xoops::getInstance()->getHandlerGroup();
        $this->userHandler = \Xoops::getInstance()->getHandlerUser();
        $this->membershipHandler = \Xoops::getInstance()->getHandlerMembership();
    }

    /**
     * create a new group
     *
     * @return XoopsGroup reference to the new group
     */
    public function createGroup()
    {
        $inst = $this->groupHandler->create();
        return $inst;
    }

    /**
     * create a new user
     *
     * @return XoopsUser reference to the new user
     */
    public function createUser()
    {
        $inst = $this->userHandler->create();
        return $inst;
    }

    /**
     * retrieve a group
     *
     * @param int $id ID for the group
     *
     * @return XoopsGroup reference to the group
     */
    public function getGroup($id)
    {
        return $this->groupHandler->get($id);
    }

    /**
     * retrieve a user
     *
     * @param int $id ID for the user
     *
     * @return XoopsUser
     */
    public function getUser($id)
    {
        if (!isset($this->membersWorkingList[$id])) {
            $this->membersWorkingList[$id] = $this->userHandler->get($id);
        }
        return $this->membersWorkingList[$id];
    }

    /**
     * delete a group
     *
     * @param XoopsGroup $group the group to delete
     *
     * @return bool FALSE if failed
     */
    public function deleteGroup(XoopsGroup $group)
    {
        $ret = $this->groupHandler->delete($group);
        $this->membershipHandler->deleteAll(new Criteria('groupid', $group->getVar('groupid')));
        return $ret;
    }

    /**
     * delete a user
     *
     * @param XoopsUser $user reference to the user to delete
     *
     * @return bool FALSE if failed
     */
    public function deleteUser(XoopsUser $user)
    {
        $ret = $this->userHandler->delete($user);
        $this->membershipHandler->deleteAll(new Criteria('uid', $user->getVar('uid')));
        return $ret;
    }

    /**
     * insert a group into the database
     *
     * @param XoopsGroup $group the group to insert
     *
     * @return bool TRUE if already in database and unchanged, FALSE on failure
     */
    public function insertGroup(XoopsGroup $group)
    {
        return $this->groupHandler->insert($group);
    }

    /**
     * insert a user into the database
     *
     * @param XoopsUser $user  the user to insert
     * @param bool      $force force insert
     *
     * @return bool TRUE if already in database and unchanged, FALSE on failure
     */
    public function insertUser(XoopsUser $user, $force = false)
    {
        return $this->userHandler->insert($user, $force);
    }

    /**
     * retrieve groups from the database
     *
     * @param CriteriaElement|null $criteria  criteria to match
     * @param bool                 $id_as_key use the group's ID as key for the array?
     *
     * @return XoopsGroup[]
     */
    public function getGroups(?CriteriaElement $criteria = null, $id_as_key = false)
    {
        return $this->groupHandler->getObjects($criteria, $id_as_key);
    }

    /**
     * retrieve users from the database
     *
     * @param CriteriaElement|null $criteria  criteria to match
     * @param bool                 $id_as_key use the group's ID as key for the array?
     *
     * @return XoopsUser[]
     */
    public function getUsers(?CriteriaElement $criteria = null, $id_as_key = false)
    {
        return $this->userHandler->getObjects($criteria, $id_as_key);
    }

    /**
     * get a list of groupnames and their IDs
     *
     * @param CriteriaElement|null $criteria criteria to match
     *
     * @return array associative array of group-IDs and names
     */
    public function getGroupList(?CriteriaElement $criteria = null)
    {
        $realCriteria = new CriteriaCompo($criteria);
        $realCriteria->add(new Criteria('groupid', FixedGroups::REMOVED, '!='));
        $groups = $this->groupHandler->getObjects($realCriteria, true);
        $ret = array();
        foreach (array_keys($groups) as $i) {
            $ret[$i] = $groups[$i]->getVar('name');
        }
        return $ret;
    }

    /**
     * get a list of usernames and their IDs
     *
     * @param CriteriaElement|null $criteria criteria to match
     *
     * @return array associative array of user-IDs and names
     */
    public function getUserList(?CriteriaElement $criteria = null)
    {
        $users = $this->userHandler->getObjects($criteria, true);
        $ret = array();
        foreach (array_keys($users) as $i) {
            $ret[$i] = $users[$i]->getVar('uname');
        }
        return $ret;
    }

    /**
     * add a user to a group
     *
     * @param int $group_id ID of the group
     * @param int $user_id  ID of the user
     *
     * @return XoopsMembership
     */
    public function addUserToGroup($group_id, $user_id)
    {
        $mship = $this->membershipHandler->create();
        $mship->setVar('groupid', $group_id);
        $mship->setVar('uid', $user_id);
        return $this->membershipHandler->insert($mship);
    }

    /**
     * remove a list of users from a group
     *
     * @param int   $group_id ID of the group
     * @param array $user_ids array of user-IDs
     *
     * @return bool success?
     */
    public function removeUsersFromGroup($group_id, $user_ids = array())
    {
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('groupid', $group_id));
        $criteria2 = new CriteriaCompo();
        foreach ($user_ids as $uid) {
            $criteria2->add(new Criteria('uid', $uid), 'OR');
        }
        $criteria->add($criteria2);
        return $this->membershipHandler->deleteAll($criteria);
    }

    /**
     * get a list of users belonging to a group
     *
     * @param int  $group_id ID of the group
     * @param bool $asobject return the users as objects?
     * @param int  $limit    number of users to return
     * @param int  $start    index of the first user to return
     *
     * @return array Array of XoopsUser objects (if $asobject is TRUE)
     *                or of associative arrays matching the record structure
     */
    public function getUsersByGroup($group_id, $asobject = false, $limit = 0, $start = 0)
    {
        $user_ids = $this->membershipHandler->getUsersByGroup($group_id, $limit, $start);
        if (!$asobject) {
            return $user_ids;
        } else {
            $ret = array();
            foreach ($user_ids as $u_id) {
                $user = $this->getUser($u_id);
                if (is_object($user)) {
                    $ret[] = $user;
                }
                unset($user);
            }
            return $ret;
        }
    }

    /**
     * get a list of groups that a user is member of
     *
     * @param int  $user_id  ID of the user
     * @param bool $asobject return groups as XoopsGroup objects or arrays?
     *
     * @return array array of objects or arrays
     */
    public function getGroupsByUser($user_id, $asobject = false)
    {
        $ret = array();
        $group_ids = $this->membershipHandler->getGroupsByUser($user_id);
        if (!$asobject) {
            return $group_ids;
        } else {
            foreach ($group_ids as $g_id) {
                $ret[] = $this->getGroup($g_id);
            }
            return $ret;
        }
    }

    /**
     * log in a user
     *
     * @param string $uname username as entered in the login form
     * @param string $pwd   password entered in the login form
     *
     * @return mixed object  XoopsUser reference to the logged in user
     *               boolean FALSE if failed to log in
     *
     * @todo - md5 support should be completely removed eventually
     */
    public function loginUser($uname, $pwd)
    {
        $criteria = new Criteria('uname', $uname);
        //$criteria->add(new Criteria('pass', md5($pwd)));
        $user = $this->userHandler->getObjects($criteria, false);
        if (!$user || count($user) != 1) {
            return false;
        }

        $hash = $user[0]->pass();
        $type = substr($user[0]->pass(), 0, 1);
        // see if we have a crypt like signature, old md5 hash is just hex digits
        if ($type==='$') {
            if (!password_verify($pwd, $hash)) {
                return false;
            }
            // check if hash uses the best algorithm (i.e. after a PHP upgrade)
            $rehash = password_needs_rehash($hash, PASSWORD_DEFAULT);
        } else {
            if ($hash!=md5($pwd)) {
                return false;
            }
            $rehash = true; // automatically update old style
        }
        // hash used an old algorithm, so make it stronger
        if ($rehash) {
            $user[0]->setVar('pass', password_hash($pwd, PASSWORD_DEFAULT));
            $this->userHandler->insert($user[0]);
        }
        return $user[0];
    }

    /**
     * count users matching certain conditions
     *
     * @param CriteriaElement|null $criteria criteria to match
     *
     * @return int
     */
    public function getUserCount(?CriteriaElement $criteria = null)
    {
        return $this->userHandler->getCount($criteria);
    }

    /**
     * count users belonging to a group
     *
     * @param int $group_id ID of the group
     *
     * @return int
     */
    public function getUserCountByGroup($group_id)
    {
        return $this->membershipHandler->getCount(new Criteria('groupid', $group_id));
    }

    /**
     * updates a single field in a users record
     *
     * @param XoopsUser $user       user object to update
     * @param string    $fieldName  name of the field to update
     * @param string    $fieldValue updated value for the field
     *
     * @return bool TRUE if success or unchanged, FALSE on failure
     */
    public function updateUserByField(XoopsUser $user, $fieldName, $fieldValue)
    {
        $user->setVar($fieldName, $fieldValue);
        return $this->insertUser($user);
    }

    /**
     * updates a single field in a users record
     *
     * @param string          $fieldName  name of the field to update
     * @param string          $fieldValue updated value for the field
     * @param CriteriaElement $criteria   criteria to match
     *
     * @return bool TRUE if success or unchanged, FALSE on failure
     */
    public function updateUsersByField($fieldName, $fieldValue, ?CriteriaElement $criteria = null)
    {
        if (is_null($criteria)) {
            $criteria = new Criteria(''); // empty criteria resolves to 'WHERE (1)'
        }
        return $this->userHandler->updateAll($fieldName, $fieldValue, $criteria);
    }

    /**
     * activate a user
     *
     * @param XoopsUser $user the user object
     *
     * @return bool successful?
     */
    public function activateUser(XoopsUser $user)
    {
        if ($user->getVar('level') != 0) {
            return true;
        }
        $user->setVar('level', 1);
        return $this->userHandler->insert($user, true);
    }

    /**
     * Get a list of users belonging to certain groups and matching criteria
     * Temporary solution
     *
     * @param array           $groups    IDs of groups
     * @param CriteriaElement $criteria  criteria to match
     * @param bool            $asobject  return the users as objects?
     * @param bool            $id_as_key use the UID as key for the array if $asobject is TRUE
     *
     * @return array Array of XoopsUser objects (if $asobject is TRUE)
     * or of associative arrays matching the record structure in the database.
     */
    public function getUsersByGroupLink(
        $groups,
        ?CriteriaElement $criteria = null,
        $asobject = false,
        $id_as_key = false
    ) {

        $qb = $this->userHandler->db2->createXoopsQueryBuilder();
        $eb = $qb->expr();

        $qb ->select('DISTINCT ' . ($asobject ? 'u.*' : 'u.uid'))
            ->fromPrefix('system_user', 'u')
            ->leftJoinPrefix('u', 'system_usergroup', 'm', 'm.uid = u.uid');

        $where = false;
        if (!empty($groups)) {
            $qb->where($eb->in('m.groupid', $groups));
            $where = true;
        }
        if (isset($criteria) && ($criteria instanceof CriteriaElement)) {
            $whereMode = $where ? 'AND' : '';
            $sql[] = $criteria->renderQb($qb, $whereMode);
        }

        $ret = array();

        if (!$result = $qb->execute()) {
            return $ret;
        }

        while ($myrow = $result->fetchAssociative()) {
            if ($asobject) {
                $user = new XoopsUser();
                $user->assignVars($myrow);
                if (!$id_as_key) {
                    $ret[] = $user;
                } else {
                    $ret[$myrow['uid']] = $user;
                }
                unset($user);
            } else {
                $ret[] = $myrow['uid'];
            }
        }
        return $ret;
    }

    /**
     * Get count of users belonging to certain groups and matching criteria
     * Temporary solution
     *
     * @param array                $groups   IDs of groups
     * @param CriteriaElement|null $criteria criteria to match
     *
     * @return int count of users
     */
    public function getUserCountByGroupLink($groups, $criteria = null)
    {
        $qb = $this->userHandler->db2->createXoopsQueryBuilder();
        $eb = $qb->expr();

        $qb ->select('COUNT(DISTINCT u.uid)')
            ->fromPrefix('system_user', 'u')
            ->leftJoinPrefix('u', 'system_usergroup', 'm', 'm.uid = u.uid');

        $where = false;
        if (!empty($groups)) {
            $qb->where($eb->in('m.groupid', $groups));
            $where = true;
        }
        if (isset($criteria) && ($criteria instanceof CriteriaElement)) {
            $whereMode = $where ? 'AND' : '';
            $criteria->renderQb($qb, $whereMode);
        }

        $result = $qb->execute();
        $ret = $result->fetchOne();

        return $ret;
    }
}
