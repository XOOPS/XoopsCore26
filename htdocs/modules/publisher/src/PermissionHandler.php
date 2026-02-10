<?php

namespace XoopsModules\Publisher;

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

use Xoops;
use Xoops\Core\Kernel\Criteria;
use Xoops\Core\Kernel\CriteriaCompo;
use Xoops\Core\Kernel\XoopsObjectHandler;

/**
 *  Publisher class
 *
 * @copyright       The XUUPS Project http://sourceforge.net/projects/xuups/
 * @license         GNU GPL V2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         Class
 * @subpackage      Handlers
 * @since           1.0
 * @author          trabis <lusopoemas@gmail.com>
 * @author          The SmartFactory <www.smartfactory.ca>
 */
require_once \dirname(__DIR__) . '/include/common.php';

/**
 * Class PermissionHandler
 * @package XoopsModules\Publisher
 */
class PermissionHandler extends XoopsObjectHandler
{
    /**
     * @var Helper
     * @access public
     */
    public $helper = null;

    /**
     * constructor
     */
    public function __construct()
    {
        $this->helper = Helper::getInstance();
        $this->db2 = Xoops::getInstance()->db();
    }

    /**
     * Returns permissions for a certain type
     *
     * @param string $gperm_name "global", "forum" or "topic" (should perhaps have "post" as well - but I don't know)
     * @param int    $id         id of the item (forum, topic or possibly post) to get permissions for
     */
    public function getGrantedGroupsById($gperm_name, $id): array
    {
        static $items;
        if (isset($items[$gperm_name][$id])) {
            return $items[$gperm_name][$id];
        }
        $groups = [];
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('gperm_modid', $this->helper->getModule()->getVar('mid')));
        $criteria->add(new Criteria('gperm_name', $gperm_name));
        $criteria->add(new Criteria('gperm_itemid', $id));
        //Instead of calling groupperm handler and get objects, we will save some memory and do it our way
        $qb = $this->db2->createXoopsQueryBuilder();
        $qb->select('gperm_groupid')->fromPrefix('system_permission', '');
        $criteria->renderQb($qb);
        $result = $qb->execute();

        while (false !== ($myrow = $result->fetchAssociative())) {
            $groups[$myrow['gperm_groupid']] = $myrow['gperm_groupid'];
        }
        $items[$gperm_name][$id] = $groups;

        return $groups;
    }

    /**
     * Returns permissions for a certain type
     *
     * @param string $gperm_name "global", "forum" or "topic" (should perhaps have "post" as well - but I don't know)
     */
    public function getGrantedItems($gperm_name): array
    {
        static $items;
        if (isset($items[$gperm_name])) {
            return $items[$gperm_name];
        }
        $ret = [];
        //Instead of calling groupperm handler and get objects, we will save some memory and do it our way
        $criteria = new CriteriaCompo(new Criteria('gperm_name', $gperm_name));
        $criteria->add(new Criteria('gperm_modid', $this->helper->getModule()->getVar('mid')));

        //Get user's groups
        $groups = Xoops::getInstance()->getUserGroups();
        $criteria2 = new CriteriaCompo();
        foreach ($groups as $gid) {
            $criteria2->add(new Criteria('gperm_groupid', $gid), 'OR');
        }
        $criteria->add($criteria2);

        $qb = $this->db2->createXoopsQueryBuilder();
        $qb->select('gperm_itemid')->fromPrefix('system_permission', '');
        $criteria->renderQb($qb);
        $result = $qb->execute();

        while (false !== ($myrow = $result->fetchAssociative())) {
            $ret[$myrow['gperm_itemid']] = $myrow['gperm_itemid'];
        }
        $items[$gperm_name] = $ret;

        return $ret;
    }

    /**
     * isGranted
     *
     * @param string $gperm_name permission name
     * @param int    $id         item id
     *
     * @return bool
     */
    public function isGranted($gperm_name, $id): ?bool
    {
        if (!$id) {
            return false;
        }
        $permissions = $this->getGrantedItems($gperm_name);
        if (!empty($permissions) && isset($permissions[$id])) {
            return true;
        }

        return false;
    }

    /**
     * Saves permissions for the selected category
     *  saveCategory_Permissions()
     *
     * @param array   $groups    group with granted permission
     * @param int $itemid    itemid on which we are setting permissions for Categories and Forums
     * @param string  $perm_name name of the permission
     *
     * @return bool : TRUE if the no errors occured
     *
     * @todo is this used anywhere?
     */
    public function saveItemPermissions($groups, $itemid, $perm_name): bool
    {
        $xoops = Xoops::getInstance();
        $result = true;
        $module_id = $this->helper->getModule()->getVar('mid');
        $gpermHandler = $xoops->getHandlerGroupPermission();
        // First, if the permissions are already there, delete them
        $gpermHandler->deleteByModule($module_id, $perm_name, $itemid);
        // Save the new permissions
        if (\count($groups) > 0) {
            foreach ($groups as $group_id) {
                echo $group_id . '-';
                echo $gpermHandler->addRight($perm_name, $itemid, $group_id, $module_id);
            }
        }

        return $result;
    }

    /**
     * Delete all permission for a specific item
     *  deletePermissions()
     *
     * @param int $itemid     id of the item for which to delete the permissions
     * @param string  $gperm_name permission name
     *
     * @return bool : TRUE if the no errors occured
     */
    public function deletePermissions($itemid, $gperm_name): bool
    {
        $xoops = Xoops::getInstance();
        $result = true;
        $gpermHandler = $xoops->getHandlerGroupPermission();
        $gpermHandler->deleteByModule($this->helper->getModule()->getVar('mid'), $gperm_name, $itemid);

        return $result;
    }
}
