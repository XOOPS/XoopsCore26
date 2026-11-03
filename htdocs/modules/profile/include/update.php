<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

/**
 * Extended User Profile
 *
 * @copyright       2000-2016 XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         profile
 * @since           2.3.0
 * @author          Taiwen Jiang <phppp@users.sourceforge.net>
 */

/**
 * @param XoopsModule $module
 * @param null $oldversion
 * @return bool
 */
function xoops_module_update_profile(&$module, $oldversion = null)
{
    global $xoopsDB;
    $xoops = Xoops::getInstance();

    if ($oldversion < 162) {
        $xoopsDB->queryF("UPDATE `" . $xoopsDB->prefix("profile_field") . " SET field_valuetype=2 WHERE field_name=umode");
    }

    if ($oldversion < 100) {

        // Drop old category table
        $sql = "DROP TABLE " . $xoopsDB->prefix("profile_category");
        $xoopsDB->queryF($sql);

        // Drop old field-category link table
        $sql = "DROP TABLE " . $xoopsDB->prefix("profile_fieldcategory");
        $xoopsDB->queryF($sql);

        // Create new tables for new profile module
        $xoopsDB->queryFromFile(\XoopsBaseConfig::get('root-path') . "/modules/" . $module->getVar('dirname', 'n') . "/sql/mysql.sql");

        include_once __DIR__ . "/install.php";
        xoops_module_install_profile($module);
        $goupperm_handler = $xoops->getHandlerGroupPermission();

        /* @var $field_handler ProfileFieldHandler */
        $field_handler = $xoops->getModuleHandler('field', $module->getVar('dirname', 'n'));
        $skip_fields = $field_handler->getUserVars();
        $skip_fields[] = 'newemail';
        $skip_fields[] = 'pm_link';
        $sql = "SELECT * FROM `" . $xoopsDB->prefix("user_profile_field") . "` WHERE `field_name` NOT IN ('" . implode("', '", $skip_fields) . "')";
        $result = $xoopsDB->query($sql);
        $fields = array();
        while (false !== ($myrow = $xoopsDB->fetchArray($result))) {
            $fields[] = $myrow['field_name'];
            $object = $field_handler->create();
            $object->setVars($myrow);
            $object->setVar('cat_id', 1);
            if (!empty($myrow['field_register'])) {
                $object->setVar('step_id', 2);
            }
            if (!empty($myrow['field_options'])) {
                $object->setVar('field_options', unserialize($myrow['field_options'], ['allowed_classes' => false]));
            }
            $field_handler->insert($object, true);

            $gperm_itemid = $object->getVar('field_id');
            $sql = "UPDATE " . $xoopsDB->prefix("system_permission") . " SET gperm_itemid = " . $gperm_itemid . "   WHERE gperm_itemid = " . $myrow['fieldid'] . "       AND gperm_modid = " . $module->getVar('mid') . "       AND gperm_name IN ('profile_edit', 'profile_search')";
            $xoopsDB->queryF($sql);

            $groups_visible = $goupperm_handler->getGroupIds("profile_visible", $myrow['fieldid'], $module->getVar('mid'));
            $groups_show = $goupperm_handler->getGroupIds("profile_show", $myrow['fieldid'], $module->getVar('mid'));
            foreach ($groups_visible as $ugid) {
                foreach ($groups_show as $pgid) {
                    $sql = "INSERT INTO " . $xoopsDB->prefix("profile_visibility") . " (field_id, user_group, profile_group) " . " VALUES " . " ({$gperm_itemid}, {$ugid}, {$pgid})";
                    $xoopsDB->queryF($sql);
                }
            }

            //profile_install_setPermissions($object->getVar('field_id'), $module->getVar('mid'), $canedit, $visible);
            unset($object);
        }

        // Copy data from profile table
        foreach ($fields as $field) {
            $xoopsDB->queryF("UPDATE `" . $xoopsDB->prefix("profile_profile") . "` u, `" . $xoopsDB->prefix("user_profile") . "` p SET u.{$field} = p.{$field} WHERE u.profile_id=p.profileid");
        }

        // Drop old profile table
        $sql = "DROP TABLE " . $xoopsDB->prefix("user_profile");
        $xoopsDB->queryF($sql);

        // Drop old field module
        $sql = "DROP TABLE " . $xoopsDB->prefix("user_profile_field");
        $xoopsDB->queryF($sql);

        // Remove not used items
        $sql = "DELETE FROM " . $xoopsDB->prefix("system_permission") . "   WHERE `gperm_modid` = " . $module->getVar('mid') . " AND `gperm_name` IN ('profile_show', 'profile_visible')";
        $xoopsDB->queryF($sql);
    }

    $profile_handler = $xoops->getModuleHandler("profile", $module->getVar('dirname', 'n'));
    $profile_handler->cleanOrphan($xoopsDB->prefix("system_user"), "uid", "profile_id");
    $field_handler = $xoops->getModuleHandler('field', $module->getVar('dirname', 'n'));
    $user_fields = $field_handler->getUserVars();
    $criteria = new Criteria("field_name", "('" . implode("', '", $user_fields) . "')", "IN");
    $field_handler->updateAll("field_config", 0, $criteria);

    return true;
}
