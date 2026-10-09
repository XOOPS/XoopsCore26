<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xmf\Module\Helper;

use Xmf\Module\Helper;
use Xoops\Core\Handler\Factory;
use Xoops\Form\SelectGroup;

/**
 * Methods to help manage permissions within a module
 *
 * @category  Xmf\Module\Helper\Permission
 * @package   Xmf
 * @author    trabis <lusopoemas@gmail.com>
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2011-2018 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
class Permission extends AbstractHelper
{
    /**
     * @var int
     */
    protected $mid;

    /**
     * @var string
     */
    protected $dirname;

    /**
     * @var \Xoops\Core\Kernel\Handlers\XoopsGroupPermHandler
     */
    protected $permissionHandler;

    /**
     * Initialize parent::__construct calls this after verifying module object.
     *
     * @return void
     */
    public function init()
    {
        $this->mid = $this->module->getVar('mid');
        $this->dirname = $this->module->getVar('dirname');
        $this->permissionHandler = Factory::newSpec()->scheme('kernel')->name('groupperm')->build();
    }

    /**
     * Check if the user has permission for an item
     *
     * @param string $gperm_name   name of the permission to test
     * @param int    $gperm_itemid id of the object to check
     * @param bool   $trueifadmin  true to always return true for admin groups
     *
     * @return bool   true if user has access, false if not
     **/
    public function checkPermission($gperm_name, $gperm_itemid, $trueifadmin = true)
    {
        $gperm_itemid = (int) $gperm_itemid;
        $gperm_groupid = \Xoops::getInstance()->getUserGroups();

        return $this->permissionHandler->checkRight(
            $gperm_name,
            $gperm_itemid,
            $gperm_groupid,
            $this->mid,
            (bool) $trueifadmin
        );
    }

    /**
     * Get all item IDs for which a group (or set of groups) has a specific permission
     *
     * @param string    $gperm_name    Name of permission
     * @param int|array $gperm_groupid A group ID or an array of group IDs
     *
     * @return array array of item IDs
     */
    public function getItemIds($gperm_name, $gperm_groupid)
    {
        return $this->permissionHandler->getItemIds(
            $gperm_name,
            $gperm_groupid,
            $this->mid
        );
    }

    /**
     * Redirect to a url if user does not have permission for an item
     *
     * @param string $gperm_name   name of the permission to test
     * @param int    $gperm_itemid id of the object to check
     * @param string $url          module relative url to redirect to
     * @param int    $time         time in seconds to delay
     * @param string $message      message to display with redirect
     * @param bool   $trueifadmin  true to always return true for admin groups
     *
     * @return void
     **/
    public function checkPermissionRedirect(
        $gperm_name,
        $gperm_itemid,
        $url,
        $time = 3,
        $message = '',
        $trueifadmin = true
    ) {
        $gperm_itemid = (int) $gperm_itemid;
        $gperm_groupid = \Xoops::getInstance()->getUserGroups();
        $permission = $this->permissionHandler->checkRight(
            $gperm_name,
            $gperm_itemid,
            $gperm_groupid,
            $this->mid,
            (bool) $trueifadmin
        );
        if (!$permission) {
            $helper = Helper::getHelper($this->dirname);
            $helper->redirect($url, $time, $message);
        }
    }

    /**
     * Get array of groups with named permission to an item
     *
     * @param string $gperm_name   name of the permission to test
     * @param int    $gperm_itemid id of the object to check
     *
     * @return array  groups with permission for item
     **/
    public function getGroupsForItem($gperm_name, $gperm_itemid)
    {
        $gperm_itemid = (int) $gperm_itemid;
        return $this->permissionHandler->getGroupIds($gperm_name, $gperm_itemid, $this->mid);
    }

    /**
     * Save group permissions for an item
     *
     * @param string $gperm_name   name of the permission to test
     * @param int    $gperm_itemid id of the object to check
     * @param array  $groups       group ids to grant permission to
     *
     * @return bool   true if no errors
     **/
    public function savePermissionForItem($gperm_name, $gperm_itemid, $groups)
    {
        $gperm_itemid = (int) $gperm_itemid;
        foreach ($groups as $index => $group) {
            $groups[$index] = (int) $group;
        }

        $result = true;

        // First, delete any existing permissions for this name and id
        $this->deletePermissionForItem($gperm_name, $gperm_itemid);

        // Save the new permissions
        if (count($groups) > 0) {
            foreach ($groups as $group_id) {
                $this->permissionHandler->addRight(
                    $gperm_name,
                    $gperm_itemid,
                    $group_id,
                    $this->mid
                );
            }
        }

        return $result;
    }

    /**
     * Delete all permissions for an item and a specific name or array of names
     *
     * @param string|string[] $gperm_name   name(s) of the permission to delete
     * @param int             $gperm_itemid id of the object to check
     *
     * @return bool   true if no errors
     */
    public function deletePermissionForItem($gperm_name, $gperm_itemid)
    {
        $gperm_itemid = (int) $gperm_itemid;
        if (!is_array($gperm_name)) {
            $gperm_name = (array) $gperm_name;
        }
        $return = true;
        foreach ($gperm_name as $pname) {
            $return = $return && $this->permissionHandler->deleteByModule($this->mid, $pname, $gperm_itemid);
        }
        return $return;
    }

    /**
     * Generate a \Xoops\Form\Element to select groups to grant permission
     * to a specific gperm_name and gperm_item. Field will be preset
     * with existing permissions.
     *
     * @param string $gperm_name   name of the permission to test
     * @param int    $gperm_itemid id of the object to check
     * @param string $caption      caption for form field
     * @param string $name         name/id of form field
     * @param bool   $include_anon true to include anonymous group
     * @param int    $size         size of list
     * @param bool   $multiple     true to allow multiple selections
     *
     * @return SelectGroup
     */
    public function getGroupSelectFormForItem(
        $gperm_name,
        $gperm_itemid,
        $caption,
        $name = null,
        $include_anon = false,
        $size = 5,
        $multiple = true
    ) {
        if (empty($name)) {
            $name = $this->defaultFieldName($gperm_name, $gperm_itemid);
        }
        $gperm_itemid = (int) $gperm_itemid;
        $value = $this->getGroupsForItem($gperm_name, $gperm_itemid);
        $element = new SelectGroup(
            $caption,
            $name,
            $include_anon,
            $value,
            $size,
            $multiple
        );

        return $element;
    }

    /**
     * Generate a default name for a Xoops\Form\SelectGroup based on
     * module, gperm_name and gperm_itemid
     *
     * @param string $gperm_name   name of the permission to test
     * @param int    $gperm_itemid id of the object to check
     *
     * @return string
     */
    public function defaultFieldName($gperm_name, $gperm_itemid)
    {
        $gperm_itemid = (int) $gperm_itemid;
        $name = $this->module->getVar('dirname') . '_' .
            $gperm_name . '_' . $gperm_itemid;

        return $name;
    }
}
