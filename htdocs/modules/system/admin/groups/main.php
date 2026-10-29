<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

use Xoops\Core\Kernel\Criteria;
use Xoops\Core\Kernel\CriteriaCompo;
use Xoops\Core\FixedGroups;
use Xmf\Request;

/**
 * Groups Manager
 *
 * @copyright       XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @author          Kazumi Ono (AKA onokazu)
 * @package         system
 * @subpackage      groups
 * @version         $Id$
 */

// Get main instance
$xoops = Xoops::getInstance();
$system = System::getInstance();
$system_breadcrumb = SystemBreadcrumb::getInstance();
// Check users rights
if (!$xoops->isUser() || !$xoops->isModule() || !$xoops->user->isAdmin($xoops->module->mid())) {
    exit(XoopsLocale::E_NO_ACCESS_PERMISSION);
}
// Parameters
$nb_group = $xoops->getModuleConfig('groups_pager', 'system');
// Get Action type
$op = Request::getString('op', 'list');
// Get groups handler
$groups_handler = $xoops->getHandlerGroup();
$member_handler = $xoops->getHandlerMember();

// Call Header
$xoops->header('admin:system/system_groups.tpl');
//$system_breadcrumb->addLink(_AM_SYSTEM_GROUPS_NAV_MANAGER, system_adminVersion('groups', 'adminpath'));

switch ($op) {

    case 'list':
    default:
        // Define Stylesheet
        $xoops->theme()->addStylesheet('modules/system/css/admin.css');
        // Define Scripts
        $xoops->theme()->addScript('media/jquery/plugins/jquery.tablesorter.js');
        $xoops->theme()->addScript('modules/system/js/admin.js');
        // Define Breadcrumb and tips
        $admin_page = new \Xoops\Module\Admin();
        $admin_page->addBreadcrumbLink(SystemLocale::CONTROL_PANEL, \XoopsBaseConfig::get('url') . '/admin.php', true);
        $admin_page->addBreadcrumbLink(SystemLocale::GROUPS_MANAGER, $system->adminVersion('groups', 'adminpath'));
        $admin_page->addBreadcrumbLink(XoopsLocale::MAIN);
        $admin_page->addItemButton(SystemLocale::ADD_NEW_GROUP, 'admin.php?fct=groups&amp;op=groups_add', 'add');
        $admin_page->addTips(SystemLocale::GROUPS_TIPS_1);
        $admin_page->renderBreadcrumb();
        $admin_page->renderTips();
        $admin_page->renderButton();
        // Get start pager
        $start = Request::getInt('start', 0);
        // Criteria
        $criteria = new CriteriaCompo();
        $criteria->setSort("groupid");
        $criteria->setOrder("ASC");
        $criteria->setStart($start);
        $criteria->setLimit($nb_group);
        $groups_arr = $groups_handler->getAll($criteria);
        // Count group
        $groups_count = count($groups_arr);
        // Assign Template variables
        $xoops->tpl()->assign('groups_count', $groups_count);
        /* @var $group XoopsGroup */
        foreach ($groups_arr as $group) {
            $groups_id = $group->getVar("groupid");
            $groups['groups_id'] = $groups_id;
            $groups['name'] = $group->getVar("name");
            $groups['description'] = $group->getVar("description");
            $member_handler = $xoops->getHandlerMember();
            if ($groups_id != FixedGroups::ANONYMOUS) {
                $group_id_arr[0] = $groups_id;
                $nb_users_by_groups = $member_handler->getUserCountByGroupLink($group_id_arr);
                $groups['nb_users_by_groups'] = sprintf(SystemLocale::F_USERS, $nb_users_by_groups);
            } else {
                $groups['nb_users_by_groups'] = '';
            }
            $edit_delete = '<a href="admin.php?fct=groups&amp;op=groups_edit&amp;groups_id=' . $groups_id . '">'
                . '<img src="./images/icons/edit.png" border="0" alt="' . SystemLocale::EDIT_GROUP
                . '" title="' . SystemLocale::EDIT_GROUP . '"></a>';
            if (!in_array($group->getVar("groupid"), array(FixedGroups::ADMIN, FixedGroups::USERS, FixedGroups::ANONYMOUS, FixedGroups::REMOVED))
            ) {
                $groups['delete'] = 1;
                $edit_delete .= '<a href="admin.php?fct=groups&amp;op=groups_delete&amp;groups_id=' . $groups_id . '">'
                    . '<img src="./images/icons/delete.png" border="0" alt="' . SystemLocale::DELETE_GROUP
                    . '" title="' . SystemLocale::DELETE_GROUP . '"></a>';
            }
            $groups['edit_delete'] = $edit_delete;
            $xoops->tpl()->appendByRef('groups', $groups);
            unset($groups, $group);
        }
        // Display Page Navigation
        if ($groups_count > $nb_group) {
            $nav = new XoopsPageNav($groups_count, $nb_group, $start, 'start', 'fct=groups&amp;op=list');
            $xoops->tpl()->assign('nav_menu', $nav->renderNav(4));
        }
        break;

    //Add a group
    case 'groups_add':
        // Define Stylesheet
        $xoops->theme()->addStylesheet('modules/system/css/admin.css');
        // Define Breadcrumb and tips
        $admin_page = new \Xoops\Module\Admin();
        $admin_page->addBreadcrumbLink(SystemLocale::CONTROL_PANEL, \XoopsBaseConfig::get('url') . '/admin.php', true);
        $admin_page->addBreadcrumbLink(SystemLocale::GROUPS_MANAGER, $system->adminVersion('groups', 'adminpath'));
        $admin_page->addBreadcrumbLink(SystemLocale::ADD_NEW_GROUP);
        $admin_page->addTips(SystemLocale::GROUPS_TIPS_2);
        $admin_page->renderBreadcrumb();
        $admin_page->renderTips();
        // Create form
        $obj = $groups_handler->create();
        $form = $xoops->getModuleForm($obj, 'group');
        // Assign form
        $xoops->tpl()->assign('form', $form->render());
        break;

    //Edit a group
    case 'groups_edit':
        // Define Stylesheet
        $xoops->theme()->addStylesheet('modules/system/css/admin.css');
        // Define Breadcrumb and tips
        $admin_page = new \Xoops\Module\Admin();
        $admin_page->addBreadcrumbLink(SystemLocale::CONTROL_PANEL, \XoopsBaseConfig::get('url') . '/admin.php', true);
        $admin_page->addBreadcrumbLink(SystemLocale::GROUPS_MANAGER, $system->adminVersion('groups', 'adminpath'));
        $admin_page->addBreadcrumbLink(SystemLocale::EDIT_GROUP);
        $admin_page->addTips(SystemLocale::GROUPS_TIPS_2);
        $admin_page->renderBreadcrumb();
        $admin_page->renderTips();
        // Create form
        $groups_id = Request::getInt('groups_id', 0);
        if ($groups_id > 0) {
            $obj = $groups_handler->get($groups_id);
            $form = $xoops->getModuleForm($obj, 'group');
            // Assign form
            $xoops->tpl()->assign('form', $form->render());
        } else {
            $xoops->redirect('admin.php?fct=groups', 1, XoopsLocale::E_DATABASE_NOT_UPDATED);
        }
        break;

    //Save a new group
    case 'groups_save_add':
        if (!$xoops->security()->check()) {
            $xoops->redirect('admin.php?fct=groups', 3, implode('<br />', $xoops->security()->getErrors()));
        }
        $system_catids = Request::getArray('system_catids', [], 'post');
        $admin_mids = Request::getArray('admin_mids', [], 'post');
        $read_mids = Request::getArray('read_mids', [], 'post');
        $read_bids = Request::getArray('read_bids', [], 'post');

        $member_handler = $xoops->getHandlerMember();
        $group = $member_handler->createGroup();
        $group->setVar('name', Request::getString('name', '', 'post'));
        $group->setVar('description', Request::getString('desc', '', 'post'));
        if (count($system_catids) > 0) {
            $group->setVar('group_type', 'Admin');
        }
        if (!$member_handler->insertGroup($group)) {
            $xoops->header();
            echo $xoops->alert('error', $group->getHtmlErrors());
            $xoops->footer();
        } else {
            $xoops->db()->beginTransaction();
            $groupid = $group->getVar('groupid');
            $gperm_handler = $xoops->getHandlerGroupPermission();
            if (count($system_catids) > 0) {
                array_push($admin_mids, 1);
                foreach ($system_catids as $s_cid) {
                    $sysperm = & $gperm_handler->create();
                    $sysperm->setVar('gperm_groupid', $groupid);
                    $sysperm->setVar('gperm_itemid', $s_cid);
                    $sysperm->setVar('gperm_name', 'system_admin');
                    $sysperm->setVar('gperm_modid', 1);
                    $gperm_handler->insert($sysperm);
                }
            }
            foreach ($admin_mids as $a_mid) {
                $modperm = & $gperm_handler->create();
                $modperm->setVar('gperm_groupid', $groupid);
                $modperm->setVar('gperm_itemid', $a_mid);
                $modperm->setVar('gperm_name', 'module_admin');
                $modperm->setVar('gperm_modid', 1);
                $gperm_handler->insert($modperm);
            }
            array_push($read_mids, 1);
            foreach ($read_mids as $r_mid) {
                $modperm = & $gperm_handler->create();
                $modperm->setVar('gperm_groupid', $groupid);
                $modperm->setVar('gperm_itemid', $r_mid);
                $modperm->setVar('gperm_name', 'module_read');
                $modperm->setVar('gperm_modid', 1);
                $gperm_handler->insert($modperm);
            }
            foreach ($read_bids as $r_bid) {
                $blockperm = & $gperm_handler->create();
                $blockperm->setVar('gperm_groupid', $groupid);
                $blockperm->setVar('gperm_itemid', $r_bid);
                $blockperm->setVar('gperm_name', 'block_read');
                $blockperm->setVar('gperm_modid', 1);
                $gperm_handler->insert($blockperm);
            }
            $xoops->db()->commit();
            $xoops->redirect('admin.php?fct=groups', 1, XoopsLocale::S_DATABASE_UPDATED);
        }
        break;

    //Save a edit group
    case 'groups_save_update':
        if (!$xoops->security()->check()) {
            $xoops->redirect('admin.php?fct=groups', 3, implode('<br />', $xoops->security()->getErrors()));
        }
        $system_catids = Request::getArray('system_catids', [], 'post');
        $admin_mids = Request::getArray('admin_mids', [], 'post');
        $read_mids = Request::getArray('read_mids', [], 'post');
        $read_bids = Request::getArray('read_bids', [], 'post');

        $member_handler = $xoops->getHandlerMember();
        $gid = Request::getInt('g_id', 0, 'post');
        if ($gid > 0) {
            $group = $member_handler->getGroup($gid);
            $group->setVar('name', Request::getString('name', '', 'post'));
            $group->setVar('description', Request::getString('desc', '', 'post'));

            // if this group is not one of the default groups
            if (!in_array($group->getVar('groupid'), array(FixedGroups::ADMIN, FixedGroups::USERS, FixedGroups::ANONYMOUS))
            ) {
                if (count($system_catids) > 0) {
                    $group->setVar('group_type', 'Admin');
                } else {
                    $group->setVar('group_type', '');
                }
            }
            if (!$member_handler->insertGroup($group)) {
                $xoops->header();
                echo $group->getHtmlErrors();
                $xoops->footer();
            } else {
                $xoops->db()->beginTransaction();
                $groupid = $group->getVar('groupid');
                $gperm_handler = $xoops->getHandlerGroupPermission();
                $criteria = new CriteriaCompo(new Criteria('gperm_groupid', $groupid));
                $criteria->add(new Criteria('gperm_modid', 1));
                $criteria2 = new CriteriaCompo(new Criteria('gperm_name', 'system_admin'));
                $criteria2->add(new Criteria('gperm_name', 'module_admin'), 'OR');
                $criteria2->add(new Criteria('gperm_name', 'module_read'), 'OR');
                $criteria2->add(new Criteria('gperm_name', 'block_read'), 'OR');
                $criteria->add($criteria2);
                $gperm_handler->deleteAll($criteria);
                if (count($system_catids) > 0) {
                    array_push($admin_mids, 1);
                    foreach ($system_catids as $s_cid) {
                        $sysperm = $gperm_handler->create();
                        $sysperm->setVar('gperm_groupid', $groupid);
                        $sysperm->setVar('gperm_itemid', $s_cid);
                        $sysperm->setVar('gperm_name', 'system_admin');
                        $sysperm->setVar('gperm_modid', 1);
                        $gperm_handler->insert($sysperm);
                    }
                }
                foreach ($admin_mids as $a_mid) {
                    $modperm = $gperm_handler->create();
                    $modperm->setVar('gperm_groupid', $groupid);
                    $modperm->setVar('gperm_itemid', $a_mid);
                    $modperm->setVar('gperm_name', 'module_admin');
                    $modperm->setVar('gperm_modid', 1);
                    $gperm_handler->insert($modperm);
                }
                array_push($read_mids, 1);
                foreach ($read_mids as $r_mid) {
                    $modperm = $gperm_handler->create();
                    $modperm->setVar('gperm_groupid', $groupid);
                    $modperm->setVar('gperm_itemid', $r_mid);
                    $modperm->setVar('gperm_name', 'module_read');
                    $modperm->setVar('gperm_modid', 1);
                    $gperm_handler->insert($modperm);
                }
                foreach ($read_bids as $r_bid) {
                    $blockperm = $gperm_handler->create();
                    $blockperm->setVar('gperm_groupid', $groupid);
                    $blockperm->setVar('gperm_itemid', $r_bid);
                    $blockperm->setVar('gperm_name', 'block_read');
                    $blockperm->setVar('gperm_modid', 1);
                    $gperm_handler->insert($blockperm);
                }
                $xoops->db()->commit();
                $xoops->redirect("admin.php?fct=groups", 1, XoopsLocale::S_DATABASE_UPDATED);
            }
        } else {
            $xoops->redirect('admin.php?fct=groups', 1, XoopsLocale::E_DATABASE_NOT_UPDATED);
        }
        break;

    //Del a group
    case 'groups_delete':
        // Define Breadcrumb and tips
        $admin_page = new \Xoops\Module\Admin();
        $admin_page->addBreadcrumbLink(SystemLocale::CONTROL_PANEL, \XoopsBaseConfig::get('url') . '/admin.php', true);
        $admin_page->addBreadcrumbLink(SystemLocale::GROUPS_MANAGER, $system->adminVersion('groups', 'adminpath'));
        $admin_page->addBreadcrumbLink(SystemLocale::DELETE_GROUP);
        $admin_page->renderBreadcrumb();
        $groups_id = Request::getInt('groups_id', 0);
        if ($groups_id > 0) {
            $obj = $groups_handler->get($groups_id);
            if (isset($_POST["ok"]) && $_POST["ok"] == 1) {
                if (!$xoops->security()->check()) {
                    $xoops->redirect("admin.php?fct=groups", 3, implode(",", $xoops->security()->getErrors()));
                }
                if ($groups_id > 0 && !in_array($groups_id, array(
                        FixedGroups::ADMIN,
                        FixedGroups::USERS,
                        FixedGroups::ANONYMOUS
                    ))
                ) {
                    $member_handler = $xoops->getHandlerMember();
                    $group = $member_handler->getGroup($groups_id);
                    $member_handler->deleteGroup($group);
                    $gperm_handler = $xoops->getHandlerGroupPermission();
                    $gperm_handler->deleteByGroup($groups_id);
                    $xoops->redirect('admin.php?fct=groups', 1, XoopsLocale::S_DATABASE_UPDATED);
                } else {
                    $xoops->redirect('admin.php?fct=groups', 2, SystemLocale::E_YOU_CANNOT_REMOVE_THIS_GROUP);
                }
            } else {
                // Define Stylesheet
                $xoops->theme()->addStylesheet('modules/system/css/admin.css');
                // Define Breadcrumb and tips
                $system_breadcrumb->addLink(SystemLocale::DELETE_GROUP);
                $system_breadcrumb->addHelp(system_adminVersion('groups', 'help') . '#edit');
                $system_breadcrumb->render();
                // Display message
                echo $xoops->confirm(
                    array(
                        "ok" => 1,
                        "groups_id" => $_REQUEST["groups_id"],
                        "op" => "groups_delete"
                    ),
                    'admin.php?fct=groups',
                    SystemLocale::Q_ARE_YOU_SURE_DELETE_THIS_GROUP . '<br />' . $obj->getVar("name") . '<br />'
                );
            }
        } else {
            $xoops->redirect('admin.php?fct=groups', 1, XoopsLocale::E_DATABASE_NOT_UPDATED);
        }
        break;

    //Add users group
    case 'action_group':
        if ('POST' !== Request::getMethod() || !$xoops->security()->check()) {
            $xoops->redirect("admin.php?fct=users", 3, implode('<br />', $xoops->security()->getErrors()));
        }
        $error = true;
        $edit_group = Request::getString('edit_group', '', 'POST');
        if ($edit_group !== '') {
            $selgroups = Request::getInt('selgroups', 0, 'POST');
            // only a webmaster may change webmaster membership
            if ($selgroups === FixedGroups::ADMIN && !in_array(FixedGroups::ADMIN, $xoops->user->getGroups())) {
                $xoops->redirect("admin.php?fct=users", 3, XoopsLocale::E_NO_ACTION_PERMISSION);
            }
            $memberslist_id = array_map('intval', Request::getArray('memberslist_id', array(), 'POST'));
            if ($edit_group === 'add_group' && $selgroups > 0) {
                foreach ($memberslist_id as $uid) {
                    $member_handler->addUserToGroup($selgroups, $uid);
                    $error = false;
                }
            } elseif ($edit_group === 'delete_group' && $selgroups > 0 && !empty($memberslist_id)) {
                $member_handler->removeUsersFromGroup($selgroups, $memberslist_id);
                $error = false;
            }
            //if ($error == true)
            $xoops->redirect("admin.php?fct=users", 1, XoopsLocale::S_DATABASE_UPDATED);
        }
        break;
}
// Call Footer
$xoops->footer();
