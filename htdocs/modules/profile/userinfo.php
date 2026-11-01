<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

use Xoops\Core\FixedGroups;
use Xoops\Html\Menu\Link;

/**
 * Extended User Profile
 *
 * @copyright       2000-2016 XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         profile
 * @since           2.3.0
 * @author          Jan Pedersen
 * @author          Taiwen Jiang <phppp@users.sourceforge.net>
 */

include __DIR__ . '/header.php';
$xoops = Xoops::getInstance();
include_once $xoops->path('modules/system/constants.php');

$uid = (int)($_GET['uid']);
if ($uid <= 0) {
    if ($xoops->isUser()) {
        $uid = $xoops->user->getVar('uid');
    } else {
        header('location: ' . \XoopsBaseConfig::get('url'));
        exit();
    }
}

$gperm_handler = $xoops->getHandlerGroupPermission();
$groups = $xoops->getUserGroups();

if ($xoops->isUser() && $uid == $xoops->user->getVar('uid')) {
    //disable cache
    $xoops->disableModuleCache();
    $xoops->header('module:profile/profile_userinfo.tpl');

    $xoops->tpl()->assign('user_ownpage', true);
    $xoops->tpl()->assign('lang_editprofile', XoopsLocale::EDIT_PROFILE);
    $xoops->tpl()->assign('lang_changepassword', _PROFILE_MA_CHANGEPASSWORD);
    $xoops->tpl()->assign('lang_avatar', XoopsLocale::AVATAR);
    $xoops->tpl()->assign('lang_inbox', XoopsLocale::INBOX);
    $xoops->tpl()->assign('lang_logout', XoopsLocale::A_LOGOUT);
    if ($xoops->getConfig('self_delete') == 1) {
        $xoops->tpl()->assign('user_candelete', true);
        $xoops->tpl()->assign('lang_deleteaccount', XoopsLocale::DELETE_ACCOUNT);
    } else {
        $xoops->tpl()->assign('user_candelete', false);
    }
    $xoops->tpl()->assign('user_changeemail', $xoops->getConfig('allow_chgmail'));
    $thisUser = $xoops->user;
} else {
    $member_handler = $xoops->getHandlerMember();
    $thisUser = $member_handler->getUser($uid);

    // Redirect if not a user or not active and the current user is not admin
    if (!is_object($thisUser) || (!$thisUser->isActive() && (!$xoops->user || !$xoops->user->isAdmin()))) {
        $xoops->redirect(\XoopsBaseConfig::get('url') . "/modules/" . $xoops->module->getVar('dirname', 'n'), 3, XoopsLocale::E_NO_USER_SELECTED);
    }

    /**
     * Access permission check
     *
     * Note:
     * "thisUser" refers to the user whose profile will be accessed; "xoopsUser" refers to the current user $xoops->user
     * "Basic Groups" refer to Xoops\Core\FixedGroups
     * "Non Basic Groups" refer to all other custom groups
     *
     * Admin groups: If thisUser belongs to admin groups, the xoopsUser has access if and only if one of xoopsUser's groups is allowed to access admin group; else
     * Non basic groups: If thisUser belongs to one or more non basic groups, the xoopsUser has access if and only if one of xoopsUser's groups is allowed to allowed to any of the non basic groups; else
     * User group: If thisUser belongs to User group only, the xoopsUser has access if and only if one of his groups is allowed to access User group
     *
     */
    // Redirect if current user is not allowed to access the user's profile based on group permission
    $groups_basic = array(FixedGroups::ADMIN, FixedGroups::USERS, FixedGroups::ANONYMOUS);
    $groups_thisUser = $thisUser->getGroups();
    $groups_thisUser_nonbasic = array_diff($groups_thisUser, $groups_basic);
    $groups_xoopsUser = $groups;
    $gperm_handler = $xoops->getHandlerGroupPermission();
    $groups_accessible = $gperm_handler->getItemIds('profile_access', $groups_xoopsUser, $xoops->module->getVar('mid'));

    $rejected = false;
    if ($thisUser->isAdmin()) {
        $rejected = !in_array(FixedGroups::ADMIN, $groups_accessible);
    } else {
        if ($groups_thisUser_nonbasic) {
            $rejected = !array_intersect($groups_thisUser_nonbasic, $groups_accessible);
        } else {
            $rejected = !in_array(FixedGroups::USERS, $groups_accessible);
        }
    }

    if ($rejected) {
        $xoops->redirect(\XoopsBaseConfig::get('url') . "/modules/" . $xoops->module->getVar('dirname', 'n'), 3, XoopsLocale::E_NO_ACCESS_PERMISSION);
    }

    if ($xoops->isUser() && $xoops->user->isAdmin()) {
        //disable cache
        $xoops->disableModuleCache();
    }
    $xoops->header('module:profile/profile_userinfo.tpl');
    $xoops->tpl()->assign('user_ownpage', false);
}

$xoops->tpl()->assign('user_uid', $thisUser->getVar('uid'));
if ($xoops->isUser() && $xoops->user->isAdmin()) {
    $xoops->tpl()->assign('lang_editprofile', XoopsLocale::EDIT_PROFILE);
    $xoops->tpl()->assign('lang_deleteaccount', XoopsLocale::DELETE_ACCOUNT);
    $xoops->tpl()->assign('userlevel', $thisUser->isActive());
    $xoops->tpl()->assign('token', $xoops->security()->getTokenHTML());
}

// Let extensions add navigation button
//$xoops->events()->triggerEvent('core.userinfo.button', array($thisUser, &$btn));
$response = $xoops->service("Avatar")->getAvatarEditUrl($thisUser);
$link=$response->getValue();
if (!empty($link)) {
    $btn[] = array( 'link' => $link, 'title' => XoopsLocale::AVATAR, 'icon' => 'icon-user');
    $xoops->tpl()->assign('btn', $btn);
}

$xoops->tpl()->assign('xoops_pagetitle', sprintf(XoopsLocale::F_ALL_ABOUT, $thisUser->getVar('uname')));

// Dynamic User Profiles
$thisUsergroups = $thisUser->getGroups();
/* @var $visibility_handler ProfileVisibilityHandler */
$visibility_handler = \Xoops::getModuleHelper('profile')->getHandler('visibility');
//search for visible Fields or null for none
$field_ids_visible = $visibility_handler->getVisibleFields($thisUsergroups, $groups);

/* @var $profile_handler ProfileProfileHandler */
$profile_handler = \Xoops::getModuleHelper('profile')->getHandler('profile');
$fields = $profile_handler->loadFields();

/* @var $category_handler ProfileCategoryHandler */
$cat_handler = \Xoops::getModuleHelper('profile')->getHandler('category');
$cat_crit = new CriteriaCompo();
$cat_crit->setSort("cat_weight");
$cats = $cat_handler->getObjects($cat_crit, true, false);
unset($cat_crit);

$response = $xoops->service("Avatar")->getAvatarUrl($thisUser);
$avatar = $response->getValue();
$avatar = empty($avatar) ? '' : $avatar;

$email = "";
if ($thisUser->getVar('user_viewemail') == 1) {
    $email = $thisUser->getVar('email', 'E');
} else {
    if ($xoops->isUser()) {
        // Module admins will be allowed to see emails
        if ($xoops->user->isAdmin() || ($xoops->user->getVar("uid") == $thisUser->getVar("uid"))) {
            $email = $thisUser->getVar('email', 'E');
        }
    }
}
$categories = array();
foreach (array_keys($cats) as $i) {
    $categories[$i] = $cats[$i];
}

$profile = $profile_handler->getProfile($thisUser->getVar('uid'));
// Add dynamic fields
/* @var ProfileField $field */
foreach ($fields as $field) {
    //If field is not visible, skip
    //if ( $field_ids_visible && !in_array($field->getVar('field_id'), $field_ids_visible) ) continue;
    if (!in_array($field->getVar('field_id'), $field_ids_visible)) {
        continue;
    }
    $cat_id = $field->getVar('cat_id');
    $value = $field->getOutputValue($thisUser, $profile);
    if (is_array($value)) {
        $value = implode('<br />', array_values($value));
    }
    if ($value) {
        $categories[$cat_id]['fields'][] = array('title' => $field->getVar('field_title'), 'value' => $value);
        $weights[$cat_id][] = $field->getVar('cat_id');
    }
}

$xoops->tpl()->assign('categories', $categories);
// Dynamic user profiles end

if ($xoops->isActiveModule('search') && $xoops->getModuleConfig('profile_search') && $xoops->getModuleConfig('enable_search', 'search')) {
    $available_plugins = \Xoops\Module\Plugin::getPlugins('search');
    $criteria = new Criteria('dirname', "('" . implode("','", array_keys($available_plugins)) . "')", 'IN');
    $modules = $module_handler->getObjectsArray($criteria, true);
    $mids = array_keys($modules);

    $myts = \Xoops\Core\Text\Sanitizer::getInstance();
    $allowed_mids = $gperm_handler->getItemIds('module_read', $groups);
    if (count($mids) > 0 && count($allowed_mids) > 0) {
        foreach ($mids as $mid) {
            if (in_array($mid, $allowed_mids)) {
                /* @var XoopsModule $module */
                $module = $modules[$mid];
                $plugin = \Xoops\Module\Plugin::getPlugin($module->getVar('dirname'), 'search');
                /* @var $plugin SearchPluginInterface */
                $results = $plugin->search('', '', 5, 0, $thisUser->getVar('uid'));
                $count = count($results);
                if (is_array($results) && $count > 0) {
                    for ($i = 0; $i < $count; ++$i) {
                        if (isset($results[$i]['image']) && $results[$i]['image'] != '') {
                            $results[$i]['image'] = \XoopsBaseConfig::get('url') . '/modules/' . $module->getVar('dirname', 'n') . '/' . $results[$i]['image'];
                        } else {
                            $results[$i]['image'] = \XoopsBaseConfig::get('url') . '/images/icons/posticon2.gif';
                        }
                        if (!preg_match("/^http[s]*:\/\//i", $results[$i]['link'])) {
                            $results[$i]['link'] = \XoopsBaseConfig::get('url') . "/modules/" . $module->getVar('dirname', 'n') . "/" . $results[$i]['link'];
                        }
                        $results[$i]['title'] = $myts->htmlSpecialChars($results[$i]['title']);
                        $results[$i]['time'] = $results[$i]['time'] ? XoopsLocale::formatTimestamp($results[$i]['time']) : '';
                    }
                    if ($count == 5) {
                        $showall_link = '<a href="' . \XoopsBaseConfig::get('url') . '/search.php?action=showallbyuser&amp;mid=' . $mid . '&amp;uid=' . $thisUser->getVar('uid') . '">' . XoopsLocale::SHOW_ALL . '</a>';
                    } else {
                        $showall_link = '';
                    }
                    $xoops->tpl()->append('modules', array(
                            'name' => $module->getVar('name'), 'results' => $results,
                            'showall_link' => $showall_link
                        ));
                }
                unset($modules[$mid], $module);
            }
        }
    }
}

//User info
$xoops->tpl()->assign('uname', $thisUser->getVar('uname'));
$xoops->tpl()->assign('email', $email);
$xoops->tpl()->assign('avatar', $avatar);
$xoops->tpl()->assign('recent_activity', _PROFILE_MA_RECENTACTIVITY);
$xoops->registry()->get('profile_breadcrumbs')->addItem(new Link(['caption' => _PROFILE_MA_USERINFO]));
include __DIR__ . '/footer.php';
