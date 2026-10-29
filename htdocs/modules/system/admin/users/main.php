<?php
//  ------------------------------------------------------------------------ //
//                XOOPS - PHP Content Management System                      //
//                    Copyright (c) 2000 XOOPS.org                           //
//                       <http://www.xoops.org/>                             //
//  ------------------------------------------------------------------------ //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //
// Author: Kazumi Ono (AKA onokazu)                                          //
// URL: http://www.myweb.ne.jp/, http://www.xoops.org/, http://jp.xoops.org/ //
// Project: The XOOPS Project                                                //
// ------------------------------------------------------------------------- //

use Xoops\Core\Kernel\Criteria;
use Xoops\Core\Kernel\CriteriaCompo;
use Xmf\Request;
use Xoops\Core\FixedGroups;

$xoops = Xoops::getInstance();

// Check users rights
if (!$xoops->isUser() || !$xoops->isModule() || !$xoops->userIsAdmin) {
    exit(XoopsLocale::E_NO_ACCESS_PERMISSION);
}

XoopsLoad::loadFile($xoops->path('modules/system/admin/users/users.php'));
// Get Action type
$op = Request::getString('op', 'default');

$member_handler = $xoops->getHandlerMember();

// Call Header
$xoops->header('admin:system/system_users.tpl');

$myts = \Xoops\Core\Text\Sanitizer::getInstance();
// Define Stylesheet
$xoops->theme()->addStylesheet('modules/system/css/admin.css');
// Define scripts
$xoops->theme()->addScript('modules/system/js/admin.js');
// Define Breadcrumb and tips
$system_breadcrumb->addLink(SystemLocale::USERS_MANAGEMENT, system_adminVersion('users', 'adminpath'));

$uid = Request::getInt('uid', 0);
switch ($op) {

    // Edit user
    case 'users_edit':
        // Assign Breadcrumb menu
        $admin_page = new \Xoops\Module\Admin();
        $admin_page->addBreadcrumbLink(SystemLocale::CONTROL_PANEL, \XoopsBaseConfig::get('url') . '/admin.php', true);
        $admin_page->addBreadcrumbLink(SystemLocale::USERS_MANAGEMENT, $system->adminVersion('users', 'adminpath'));
        $admin_page->addBreadcrumbLink(SystemLocale::EDIT_USER);
        $admin_page->renderBreadcrumb();
        $uid = Request::getInt('uid', 0, 'get');
        $member_handler = $xoops->getHandlerMember();
        $user = $member_handler->getUser($uid);
        $form = $xoops->getModuleForm($user, 'user');
        $form->display();
        break;

    // Add user
    case 'users_add':
        // Assign Breadcrumb menu
        $admin_page = new \Xoops\Module\Admin();
        $admin_page->addBreadcrumbLink(SystemLocale::CONTROL_PANEL, \XoopsBaseConfig::get('url') . '/admin.php', true);
        $admin_page->addBreadcrumbLink(SystemLocale::USERS_MANAGEMENT, $system->adminVersion('users', 'adminpath'));
        $admin_page->addBreadcrumbLink(SystemLocale::ADD_USER);
        $admin_page->renderBreadcrumb();
        $member_handler = $xoops->getHandlerMember();
        $user = $member_handler->createUser();
        $form = $xoops->getModuleForm($user, 'user');
        $form->display();
        break;

    // Delete user
    case 'users_delete':
        // Assign Breadcrumb menu
        $admin_page = new \Xoops\Module\Admin();
        $admin_page->addBreadcrumbLink(SystemLocale::CONTROL_PANEL, \XoopsBaseConfig::get('url') . '/admin.php', true);
        $admin_page->addBreadcrumbLink(SystemLocale::USERS_MANAGEMENT, $system->adminVersion('users', 'adminpath'));
        $admin_page->addBreadcrumbLink(SystemLocale::DELETE_USER);
        $admin_page->renderBreadcrumb();
        $system_breadcrumb->render();
        $user = $member_handler->getUser($uid);
        if (isset($_REQUEST['ok']) && $_REQUEST['ok'] == 1) {
            if (!$xoops->security()->check()) {
                $xoops->redirect("admin.php?fct=users", 3, implode('<br />', $xoops->security()->getErrors()));
            }

            $groups = $user->getGroups();
            if (in_array(FixedGroups::ADMIN, $groups)) {
                echo $xoops->alert('error', sprintf(SystemLocale::EF_CAN_NOT_DELETE_ADMIN_USER, $user->getVar("uname")));
            } elseif (!$member_handler->deleteUser($user)) {
                echo $xoops->alert('error', sprintf(SystemLocale::EF_COULD_NOT_DELETE_USER, $user->getVar("uname")));
            } else {
                $xoops->getHandlerOnline()->destroy($uid);
                if ($xoops->isActiveModule('notifications')) {
                    Notifications::getInstance()->getHandlerNotification()->unsubscribeByUser($uid);
                }
                $xoops->redirect("admin.php?fct=users", 1, XoopsLocale::S_DATABASE_UPDATED);
            }
        } else {
            //Assign Breadcrumb menu
            $system_breadcrumb->addHelp(system_adminVersion('users', 'help') . '#delete');
            $system_breadcrumb->addLink(SystemLocale::DELETE_USER);
            $system_breadcrumb->render();
            echo $xoops->confirm(array(
                'ok' => 1, 'uid' => $uid, 'op' => 'users_delete'
            ), "admin.php?fct=users", sprintf(SystemLocale::F_DELETE_USER, $user->getVar('uname')) . '<br />');
        }
        break;

    // Delete users
    case "action_group":
        if ('POST' !== Request::getMethod() || !$xoops->security()->check()) {
            $xoops->redirect("admin.php?fct=users", 3, implode('<br />', $xoops->security()->getErrors()));
        }
        $memberslist_id = Request::getArray('memberslist_id', array(), 'POST');
        if (!empty($memberslist_id)) {
            $system_breadcrumb->render();
            $error = '';
            foreach ($memberslist_id as $del) {
                $del = (int)($del);
                $user = $member_handler->getUser($del);
                $groups = $user->getGroups();
                if (in_array(FixedGroups::ADMIN, $groups)) {
                    $error .= sprintf(SystemLocale::EF_CAN_NOT_DELETE_ADMIN_USER, $user->getVar("uname"));
                    $error .= '<br />';
                } elseif (!$member_handler->deleteUser($user)) {
                    $error .= sprintf(SystemLocale::EF_COULD_NOT_DELETE_USER, $user->getVar("uname"));
                    $error .= '<br />';
                } else {
                    $xoops->getHandlerOnline()->destroy($del);
                    // RMV-NOTIFY
                    if ($xoops->isActiveModule('notifications')) {
                        Notifications::getInstance()->getHandlerNotification()->unsubscribeByUser($del);
                    }
                }
            }
            if ($error != '') {
                $xoops->redirect("admin.php?fct=users", 3, sprintf(XoopsLocale::F_ERROR, $error));
            } else {
                $xoops->redirect("admin.php?fct=users", 1, XoopsLocale::S_DATABASE_UPDATED);
            }
        }
        break;

    // Save user
    case "users_save":
        $can_admin_groups = $xoops->getHandlerGroupPermission()
            ->checkRight("system_admin", XOOPS_SYSTEM_GROUP, $xoops->user->getGroups(), 1);
        $req_groups = array_map('intval', Request::getArray('groups', array(), 'POST'));
        // only a webmaster may grant webmaster membership
        if (!in_array(FixedGroups::ADMIN, $xoops->user->getGroups())) {
            $req_groups = array_values(array_diff($req_groups, array(FixedGroups::ADMIN)));
        }
        if (isset($_REQUEST['uid'])) {
            //Update user
            if (!$xoops->security()->check()) {
                $xoops->redirect("admin.php?fct=users", 3, implode('<br />', $xoops->security()->getErrors()));
            }
            // RMV-NOTIFY
            $user_avatar = $theme = null;
            if (!isset($_REQUEST['attachsig'])) {
                $attachsig = null;
            }
            if (!isset($_REQUEST['user_viewemail'])) {
                $user_viewemail = null;
            }

            $edituser = $member_handler->getUser($uid);
            if (!is_object($edituser)
                || (in_array(FixedGroups::ADMIN, $edituser->getGroups())
                    && !in_array(FixedGroups::ADMIN, $xoops->user->getGroups()))
            ) {
                // only a webmaster may change a webmaster
                $xoops->redirect("admin.php?fct=users", 3, XoopsLocale::E_NO_ACTION_PERMISSION);
            }
            if ($edituser->getVar('uname', 'n') != $_REQUEST['username'] && $member_handler->getUserCount(new Criteria('uname', $_REQUEST['username'])) > 0) {
                $xoops->header();
                echo $xoops->alert('error', sprintf(XoopsLocale::EF_USER_NAME_ALREADY_EXISTS, $myts->htmlSpecialChars($_REQUEST['username'])));
                $xoops->footer();
            } elseif ($edituser->getVar('email', 'n') != $_REQUEST['email'] && $member_handler->getUserCount(new Criteria('email', $_REQUEST['email'])) > 0) {
                $xoops->header();
                echo $xoops->alert('error', sprintf(XoopsLocale::EF_EMAIL_ALREADY_EXISTS, $myts->htmlSpecialChars($_REQUEST['email'])));
                $xoops->footer();
            } else {
                $edituser->setVar("name", $_REQUEST['name']);
                $edituser->setVar("uname", $_REQUEST['username']);
                $edituser->setVar("email", $_REQUEST['email']);
                $url = isset($_REQUEST['url']) ? $xoops->formatURL($_REQUEST['url']) : '';
                $edituser->setVar("url", $url);
                $edituser->setVar("user_icq", $_REQUEST['user_icq']);
                $edituser->setVar("user_from", $_REQUEST['user_from']);
                $edituser->setVar("user_sig", $_REQUEST['user_sig']);
                $user_viewemail = (isset($_REQUEST['user_viewemail']) && $_REQUEST['user_viewemail'] == 1) ? 1 : 0;
                $edituser->setVar("user_viewemail", $user_viewemail);
                $edituser->setVar("user_aim", $_REQUEST['user_aim']);
                $edituser->setVar("user_yim", $_REQUEST['user_yim']);
                $edituser->setVar("user_msnm", $_REQUEST['user_msnm']);
                $attachsig = (isset($_REQUEST['attachsig']) && $_REQUEST['attachsig'] == 1) ? 1 : 0;
                $edituser->setVar("attachsig", $attachsig);
                $edituser->setVar("timezone", $_REQUEST['timezone']);
                //$edituser->setVar("uorder", $_REQUEST['uorder']);
                //$edituser->setVar("umode", $_REQUEST['umode']);
                // RMV-NOTIFY
                //$edituser->setVar("notify_method", $_REQUEST['notify_method']);
                //$edituser->setVar("notify_mode", $_REQUEST['notify_mode']);
                $edituser->setVar("bio", $_REQUEST['bio']);
                $edituser->setVar("rank", $_REQUEST['rank']);
                $edituser->setVar("user_occ", $_REQUEST['user_occ']);
                $edituser->setVar("user_intrest", $_REQUEST['user_intrest']);
                $edituser->setVar('user_mailok', $_REQUEST['user_mailok']);
                if ($_REQUEST['pass2'] != "") {
                    if ($_REQUEST['password'] != $_REQUEST['pass2']) {
                        $xoops->header();
                        echo "
                        <strong>" . SystemLocale::E_NEW_PASSWORDS_NOT_MATCH_TRY_AGAIN . "</strong>";
                        $xoops->footer();
                        exit();
                    }
                    $edituser->setVar("pass", password_hash($_REQUEST['password'], PASSWORD_DEFAULT));
                }
                if (!$member_handler->insertUser($edituser)) {
                    $xoops->header();
                    echo $edituser->getHtmlErrors();
                    $xoops->footer();
                } else {
                    // membership changes need the group-administration right, like the form
                    if ($can_admin_groups && $req_groups != array()) {
                        $oldgroups = $edituser->getGroups();
                        //If the edited user is the current user and the current user WAS in the webmaster's group and is NOT in the new groups array
                        if ($edituser->getVar('uid') == $xoops->user->getVar('uid') && (in_array(FixedGroups::ADMIN, $oldgroups)) && !(in_array(FixedGroups::ADMIN, $req_groups))) {
                            //Add the webmaster's group to the groups array to prevent accidentally removing oneself from the webmaster's group
                            $req_groups[] = FixedGroups::ADMIN;
                        }
                        $member_handler = $xoops->getHandlerMember();
                        foreach ($oldgroups as $groupid) {
                            $member_handler->removeUsersFromGroup($groupid, array($edituser->getVar('uid')));
                        }
                        foreach ($req_groups as $groupid) {
                            $member_handler->addUserToGroup($groupid, $edituser->getVar('uid'));
                        }
                    }
                    $xoops->redirect("admin.php?fct=users", 1, XoopsLocale::S_DATABASE_UPDATED);
                }
            }
            exit();
        } else {
            //Add user
            if (!$xoops->security()->check()) {
                $xoops->redirect("admin.php?fct=users", 3, implode('<br />', $xoops->security()->getErrors()));
            }
            if (!$_REQUEST['username'] || !$_REQUEST['email'] || !$_REQUEST['password']) {
                $adduser_errormsg = XoopsLocale::E_YOU_MUST_COMPLETE_ALL_REQUIRED_FIELDS;
            } else {
                $member_handler = $xoops->getHandlerMember();
                // make sure the username doesnt exist yet
                if ($member_handler->getUserCount(new Criteria('uname', $_REQUEST['username'])) > 0) {
                    $adduser_errormsg = 'User name ' . $myts->htmlSpecialChars($_REQUEST['username']) . ' already exists';
                } else {
                    $newuser = $member_handler->createUser();
                    if (isset($user_viewemail)) {
                        $newuser->setVar("user_viewemail", $_REQUEST['user_viewemail']);
                    }
                    if (isset($attachsig)) {
                        $newuser->setVar("attachsig", $_REQUEST['attachsig']);
                    }
                    $newuser->setVar("name", $_REQUEST['name']);
                    $newuser->setVar("uname", $_REQUEST['username']);
                    $newuser->setVar("email", $_REQUEST['email']);
                    $newuser->setVar("url", $xoops->formatURL($_REQUEST['url']));
                    $newuser->setVar("user_avatar", 'blank.gif');
                    $newuser->setVar('user_regdate', time());
                    $newuser->setVar("user_icq", $_REQUEST['user_icq']);
                    $newuser->setVar("user_from", $_REQUEST['user_from']);
                    $newuser->setVar("user_sig", $_REQUEST['user_sig']);
                    $newuser->setVar("user_aim", $_REQUEST['user_aim']);
                    $newuser->setVar("user_yim", $_REQUEST['user_yim']);
                    $newuser->setVar("user_msnm", $_REQUEST['user_msnm']);
                    if ($_REQUEST['pass2'] != "") {
                        if ($_REQUEST['password'] != $_REQUEST['pass2']) {
                            $xoops->header();
                            echo "<strong>" . SystemLocale::E_NEW_PASSWORDS_NOT_MATCH_TRY_AGAIN . "</strong>";
                            $xoops->footer();
                            exit();
                        }
                        $newuser->setVar("pass", password_hash($_REQUEST['password'], PASSWORD_DEFAULT));
                    }
                    $newuser->setVar("timezone", $_REQUEST['timezone']);
                    //$newuser->setVar("uorder", $_REQUEST['uorder']);
                    //$newuser->setVar("umode", $_REQUEST['umode']);
                    // RMV-NOTIFY
                    //$newuser->setVar("notify_method", $_REQUEST['notify_method']);
                    //$newuser->setVar("notify_mode", $_REQUEST['notify_mode']);
                    $newuser->setVar("bio", $_REQUEST['bio']);
                    $newuser->setVar("rank", $_REQUEST['rank']);
                    $newuser->setVar("level", 1);
                    $newuser->setVar("user_occ", $_REQUEST['user_occ']);
                    $newuser->setVar("user_intrest", $_REQUEST['user_intrest']);
                    $newuser->setVar('user_mailok', $_REQUEST['user_mailok']);
                    if (!$member_handler->insertUser($newuser)) {
                        $adduser_errormsg = XoopsLocale::E_USER_NOT_REGISTERED;
                    } else {
                        $groups_failed = array();
                        $new_groups = $can_admin_groups ? $req_groups : array(FixedGroups::USERS);
                        foreach ($new_groups as $group) {
                            $group = (int)($group);
                            if (!$member_handler->addUserToGroup($group, $newuser->getVar('uid'))) {
                                $groups_failed[] = $group;
                            }
                        }
                        if (!empty($groups_failed)) {
                            $group_names = $member_handler->getGroupList(new Criteria('groupid', "(" . implode(", ", $groups_failed) . ")", 'IN'));
                            $adduser_errormsg = sprintf(SystemLocale::EF_COULD_NOT_ADD_USER_TO_GROUPS, implode(", ", $group_names));
                        } else {
                            XoopsUserUtility::sendWelcome($newuser);
                            $xoops->redirect("admin.php?fct=users", 1, XoopsLocale::S_DATABASE_UPDATED);
                            exit();
                        }
                    }
                }
            }
            echo $xoops->alert('error', $adduser_errormsg);
        }
        break;

    // Activ member
    case 'users_active':
        if (!$xoops->security()->check()) {
            $xoops->redirect("admin.php?fct=users", 3, implode('<br />', $xoops->security()->getErrors()));
        }
        $obj = $member_handler->getUser($uid);
        if (!is_object($obj)
            || (in_array(FixedGroups::ADMIN, $obj->getGroups())
                && !in_array(FixedGroups::ADMIN, $xoops->user->getGroups()))
        ) {
            // only a webmaster may change a webmaster
            $xoops->redirect("admin.php?fct=users", 3, XoopsLocale::E_NO_ACTION_PERMISSION);
        }
        $obj->setVar("level", 1);
        if ($member_handler->insertUser($obj, true)) {
            $xoops->redirect("admin.php?fct=users", 1, XoopsLocale::S_DATABASE_UPDATED);
        }
        echo $obj->getHtmlErrors();
        break;

    // Synchronize
    case 'users_synchronize':
        if (isset($_REQUEST['status']) && $_REQUEST['status'] == 1) {
            synchronize($uid, 'user');
        } else {
            if (isset($_REQUEST['status']) && $_REQUEST['status'] == 2) {
                synchronize('', 'all users');
            }
        }
        $xoops->redirect("admin.php?fct=users", 1, XoopsLocale::S_DATABASE_UPDATED);
        break;

    default:
        // Search and Display
        // Define scripts
        $xoops->theme()->addBaseScriptAssets('@jqueryui', 'modules/system/js/admin.js');
        //table sorting does not work with select boxes
        //$xoops->theme()->addScript('media/jquery/plugins/jquery.tablesorter.js');
        //$xoops->theme()->addScript('modules/system/js/admin.js');
        //Recherche approfondie

        if (isset($_REQUEST['complet_search'])) {
            // Assign Breadcrumb menu
            $admin_page = new \Xoops\Module\Admin();
            $admin_page->addBreadcrumbLink(SystemLocale::CONTROL_PANEL, \XoopsBaseConfig::get('url') . '/admin.php', true);
            $admin_page->addBreadcrumbLink(SystemLocale::USERS_MANAGEMENT, $system->adminVersion('users', 'adminpath'));
            $admin_page->addBreadcrumbLink(XoopsLocale::ADVANCED_SEARCH);
            $admin_page->renderBreadcrumb();

            $acttotal = $member_handler->getUserCount(new Criteria('level', 0, '>'));
            $inacttotal = $member_handler->getUserCount(new Criteria('level', 0));
            $group_select = new Xoops\Form\Select(XoopsLocale::GROUPS, "selgroups");
            $group_handler = $xoops->getHandlerGroup();
            $group_arr = $group_handler->getObjects();
            $group_select->addOption("", "--------------");
            /* @var $group XoopsGroup */
            foreach ($group_arr as $group) {
                if ($group->getVar("groupid") != FixedGroups::ANONYMOUS) {
                    $group_select->addOption("" . $group->getVar("groupid") . "", "" . $group->getVar("name") . "");
                }
            }
            unset($group);
            $uname_text = new Xoops\Form\Text("", "user_uname", 30, 60);
            $uname_match = new Xoops\Form\SelectMatchOption("", "user_uname_match");
            $uname_tray = new Xoops\Form\ElementTray(XoopsLocale::USER_NAME, "&nbsp;");
            $uname_tray->addElement($uname_match);
            $uname_tray->addElement($uname_text);
            $name_text = new Xoops\Form\Text("", "user_name", 30, 60);
            $name_match = new Xoops\Form\SelectMatchOption("", "user_name_match");
            $name_tray = new Xoops\Form\ElementTray(XoopsLocale::REAL_NAME, "&nbsp;");
            $name_tray->addElement($name_match);
            $name_tray->addElement($name_text);
            $email_text = new Xoops\Form\Text("", "user_email", 30, 60);
            $email_match = new Xoops\Form\SelectMatchOption("", "user_email_match");
            $email_tray = new Xoops\Form\ElementTray(XoopsLocale::EMAIL, "&nbsp;");
            $email_tray->addElement($email_match);
            $email_tray->addElement($email_text);
            $url_text = new Xoops\Form\Text(XoopsLocale::URL_CONTAINS, "user_url", 30, 100);
            $icq_text = new Xoops\Form\Text("", "user_icq", 30, 100);
            $icq_match = new Xoops\Form\SelectMatchOption("", "user_icq_match");
            $icq_tray = new Xoops\Form\ElementTray(XoopsLocale::ICQ, "&nbsp;");
            $icq_tray->addElement($icq_match);
            $icq_tray->addElement($icq_text);
            $aim_text = new Xoops\Form\Text("", "user_aim", 30, 100);
            $aim_match = new Xoops\Form\SelectMatchOption("", "user_aim_match");
            $aim_tray = new Xoops\Form\ElementTray(XoopsLocale::AIM, "&nbsp;");
            $aim_tray->addElement($aim_match);
            $aim_tray->addElement($aim_text);
            $yim_text = new Xoops\Form\Text("", "user_yim", 30, 100);
            $yim_match = new Xoops\Form\SelectMatchOption("", "user_yim_match");
            $yim_tray = new Xoops\Form\ElementTray(XoopsLocale::YIM, "&nbsp;");
            $yim_tray->addElement($yim_match);
            $yim_tray->addElement($yim_text);
            $msnm_text = new Xoops\Form\Text("", "user_msnm", 30, 100);
            $msnm_match = new Xoops\Form\SelectMatchOption("", "user_msnm_match");
            $msnm_tray = new Xoops\Form\ElementTray(XoopsLocale::MSNM, "&nbsp;");
            $msnm_tray->addElement($msnm_match);
            $msnm_tray->addElement($msnm_text);
            $location_text = new Xoops\Form\Text(XoopsLocale::LOCATION_CONTAINS, "user_from", 30, 100);
            $occupation_text = new Xoops\Form\Text(XoopsLocale::OCCUPATION_CONTAINS, "user_occ", 30, 100);
            $interest_text = new Xoops\Form\Text(XoopsLocale::INTEREST_CONTAINS, "user_intrest", 30, 100);

            $lastlog_more = new Xoops\Form\Text(SystemLocale::LAST_LOGIN_GREATER_THAN_X, "user_lastlog_more", 10, 5);
            $lastlog_less = new Xoops\Form\Text(SystemLocale::LAST_LOGIN_LESS_THAN_X, "user_lastlog_less", 10, 5);
            $reg_more = new Xoops\Form\Text(SystemLocale::REGISTRATION_DATE_GREATER_THAN_X, "user_reg_more", 10, 5);
            $reg_less = new Xoops\Form\Text(SystemLocale::REGISTRATION_DATE_LESS_THAN_X, "user_reg_less", 10, 5);
            $posts_more = new Xoops\Form\Text(SystemLocale::POSTS_NUMBER_GREATER_THAN_X, "user_posts_more", 10, 5);
            $posts_less = new Xoops\Form\Text(SystemLocale::POSTS_NUMBER_LESS_THAN_X, "user_posts_less", 10, 5);
            $mailok_radio = new Xoops\Form\Radio(XoopsLocale::TYPE_OF_USERS_TO_SHOW, "user_mailok", "both");
            $mailok_radio->addOptionArray(array(
                "mailok" => XoopsLocale::ONLY_USERS_THAT_ACCEPT_EMAIL, "mailng" => XoopsLocale::ONLY_USERS_THAT_DO_NOT_ACCEPT_EMAIL,
                "both"   => XoopsLocale::ALL_USERS
            ));
            $type_radio = new Xoops\Form\Radio(XoopsLocale::TYPE_OF_USERS_TO_SHOW, "user_type", "both");
            $type_radio->addOptionArray(array(
                "actv" => SystemLocale::ONLY_ACTIVE_USERS, "inactv" => SystemLocale::ONLY_INACTIVE_USERS,
                "both" => XoopsLocale::ALL_USERS
            ));
            $sort_select = new Xoops\Form\Select(XoopsLocale::SORT_BY, "user_sort", 'uname');
            $sort_select->addOptionArray(array(
                "uname"      => XoopsLocale::USER_NAME, "email" => XoopsLocale::EMAIL,
                "last_login" => XoopsLocale::LAST_LOGIN, "user_regdate" => XoopsLocale::REGISTRATION_DATE,
                "posts"      => XoopsLocale::COMMENTS_POSTS
            ));
            $order_select = new Xoops\Form\Select(XoopsLocale::ORDER, "user_order", 'ASC');
            $order_select->addOptionArray(array("ASC" => XoopsLocale::ASCENDING, "DESC" => XoopsLocale::DESCENDING));
            $limit_text = new Xoops\Form\Text(XoopsLocale::NUMBER_OF_RESULTS_PER_PAGE, "user_limit", 6, 2, 20);
            $submit_button = new Xoops\Form\Button("", "user_submit", XoopsLocale::A_SUBMIT, "submit");

            $form = new Xoops\Form\ThemeForm(XoopsLocale::FIND_USERS, "user_findform", "admin.php?fct=users", 'post', true);
            $form->addElement($uname_tray);
            $form->addElement($name_tray);
            $form->addElement($email_tray);
            $form->addElement($group_select);
            $form->addElement($icq_tray);
            $form->addElement($aim_tray);
            $form->addElement($yim_tray);
            $form->addElement($msnm_tray);
            $form->addElement($url_text);
            $form->addElement($location_text);
            $form->addElement($occupation_text);
            $form->addElement($interest_text);
            $form->addElement($lastlog_more);
            $form->addElement($lastlog_less);
            $form->addElement($reg_more);
            $form->addElement($reg_less);
            $form->addElement($posts_more);
            $form->addElement($posts_less);
            $form->addElement($mailok_radio);
            $form->addElement($type_radio);
            $form->addElement($sort_select);
            $form->addElement($order_select);
            $form->addElement($limit_text);

            // if this is to find users for a specific group
            if (!empty($_GET['group']) && (int)($_GET['group']) > 0) {
                $group_hidden = new Xoops\Form\Hidden("group", (int)($_GET['group']));
                $form->addElement($group_hidden);
            }
            $form->addElement($submit_button);
            $form->display();
        } else {
            //Display data
            // Assign Breadcrumb menu
            $admin_page = new \Xoops\Module\Admin();
            $admin_page->addBreadcrumbLink(SystemLocale::CONTROL_PANEL, \XoopsBaseConfig::get('url') . '/admin.php', true);
            $admin_page->addBreadcrumbLink(SystemLocale::USERS_MANAGEMENT, $system->adminVersion('users', 'adminpath'));
            $admin_page->addBreadcrumbLink(XoopsLocale::LIST_);
            $admin_page->renderBreadcrumb();
            $admin_page->addTips(SystemLocale::USERS_TIPS);
            $admin_page->renderTips();
            $admin_page->addItemButton(XoopsLocale::A_SYNCHRONIZE, 'admin.php?fct=users&amp;op=users_synchronize&amp;status=2', 'arrow-rotate-anticlockwise');
            $admin_page->addItemButton(SystemLocale::ADD_USER, 'admin.php?fct=users&amp;op=users_add', 'add');
            $admin_page->renderButton();

            $requete_search = '<br /><br /><strong>' . SystemLocale::C_SEE_SEARCH_REQUEST . '</strong><br /><br />';
            $requete_pagenav = '';

            $criteria = new CriteriaCompo();

            $value = Request::getString('user_uname', '');
            if (!empty($value)) {
                $match = Request::getInt('user_uname_match', XOOPS_MATCH_START);
                addCriteria($criteria, 'uname', $value, $match);
                $requete_pagenav .= '&amp;user_uname=' . $myts->htmlSpecialChars($value) . '&amp;user_uname_match=' . $match;
                $requete_search .= 'uname : ' . $value . ' and user_uname_match=' . $match . '<br />';
            }

            $value = Request::getString('user_name', '');
            if (!empty($value)) {
                $match = Request::getInt('user_name_match', XOOPS_MATCH_START);
                addCriteria($criteria, 'name', $value, $match);
                $requete_pagenav .= '&amp;user_name=' . $myts->htmlSpecialChars($value) . '&amp;user_name_match=' . $match;
                $requete_search .= 'name : ' . $value . ' and user_name_match=' . $match . '<br />';
            }

            $value = Request::getString('user_email', '');
            if (!empty($value)) {
                $match = Request::getInt('user_email_match', XOOPS_MATCH_START);
                addCriteria($criteria, 'email', $value, $match);
                $requete_pagenav .= '&amp;user_email=' . $myts->htmlSpecialChars($value) . '&amp;user_email_match=' . $match;
                $requete_search .= 'email : ' . $value . ' and user_email_match=' . $match . '<br />';
            }

            $value = Request::getString('user_url', '');
            if (!empty($value)) {
                //$url = $xoops->formatURL(trim($_REQUEST['user_url']));
                $criteria->add(new Criteria('url', '%' . $value . '%', 'LIKE'));
                $requete_search .= 'url : ' . $value . '<br />';
            }

            $value = Request::getInt('user_icq', 0);
            if (!empty($value)) {
                $match = Request::getInt('user_icq_match', XOOPS_MATCH_START);
                addCriteria($criteria, 'user_icq', (string) $value, $match);
                $requete_pagenav .= '&amp;user_icq=' . $value . '&amp;user_icq_match=' . $match;
                $requete_search .= 'icq : ' . $value . ' and user_icq_match=' . $match . '<br />';
            }

            $value = Request::getString('user_aim', '');
            if (!empty($value)) {
                $match = Request::getInt('user_aim_match', XOOPS_MATCH_START);
                addCriteria($criteria, 'user_aim', $value, $match);
                $requete_pagenav .= '&amp;user_aim=' . $myts->htmlSpecialChars($value) . '&amp;user_aim_match=' . $match;
                $requete_search .= 'aim : ' . $value . ' and user_aim_match=' . $match . '<br />';
            }

            $value = Request::getString('user_yim', '');
            if (!empty($value)) {
                $match = Request::getInt('user_yim_match', XOOPS_MATCH_START);
                addCriteria($criteria, 'user_yim', $value, $match);
                $requete_pagenav .= '&amp;user_yim=' . $myts->htmlSpecialChars($value) . '&amp;user_yim_match=' . $match;
                $requete_search .= 'yim : ' . $value . ' and user_yim_match=' . $match . '<br />';
            }

            $value = Request::getString('user_msnm', '');
            if (!empty($value)) {
                $match = Request::getInt('user_msnm_match', XOOPS_MATCH_START);
                addCriteria($criteria, 'user_msnm', $value, $match);
                $requete_pagenav .= '&amp;user_msnm=' . $myts->htmlSpecialChars($value) . '&amp;user_msnm_match=' . $match;
                $requete_search .= 'msnm : ' . $value . ' and user_msnm_match=' . $match . '<br />';
            }

            $value = Request::getString('user_from', '');
            if (!empty($value)) {
                $criteria->add(new Criteria('user_from', '%' . $value . '%', 'LIKE'));
                $requete_pagenav .= '&amp;user_from=' . $myts->htmlSpecialChars($value);
                $requete_search .= 'from : ' . $value . '<br />';
            }

            $value = Request::getString('user_intrest', '');
            if (!empty($value)) {
                $criteria->add(new Criteria('user_intrest', '%' . $value . '%', 'LIKE'));
                $requete_pagenav .= '&amp;user_intrest=' . $myts->htmlSpecialChars($value);
                $requete_search .= 'interet : ' . $value . '<br />';
            }

            $value = Request::getString('user_occ', '');
            if (!empty($value)) {
                $criteria->add(new Criteria('user_occ', '%' . $value . '%', 'LIKE'));
                $requete_pagenav .= '&amp;user_occ=' . $myts->htmlSpecialChars($value);
                $requete_search .= 'location : ' . $value . '<br />';
            }

            $value = Request::getInt('user_lastlog_more', 0);
            if (!empty($value)) {
                $time = time() - (60 * 60 * 24 * $value);
                if ($time > 0) {
                    $criteria->add(new Criteria('last_login', $time, '<'));
                    $requete_pagenav .= '&amp;user_lastlog_more=' . $value;
                    $requete_search .= 'derniere connexion apres : ' . $value . '<br />';
                }
            }

            $value = Request::getInt('user_lastlog_less', 0);
            if (!empty($value)) {
                $time = time() - (60 * 60 * 24 * $value);
                if ($time > 0) {
                    $criteria->add(new Criteria('last_login', $time, '>'));
                    $requete_pagenav .= '&amp;user_lastlog_less=' . $value;
                    $requete_search .= 'derniere connexion avant : ' . $value . '<br />';
                }
            }

            $value = Request::getInt('user_reg_more', 0);
            if (!empty($value)) {
                $time = time() - (60 * 60 * 24 * $value);
                if ($time > 0) {
                    $criteria->add(new Criteria('user_regdate', $time, '<'));
                    $requete_pagenav .= '&amp;user_reg_more=' . $value;
                    $requete_search .= 'enregistre apres : ' . $value . '<br />';
                }
            }

            $value = Request::getInt('user_reg_less', 0);
            if (!empty($value)) {
                $time = time() - (60 * 60 * 24 * $value);
                if ($time > 0) {
                    $criteria->add(new Criteria('user_regdate', $time, '>'));
                    $requete_pagenav .= '&amp;user_reg_less=' . $value;
                    $requete_search .= 'enregistre avant : ' . $value . '<br />';
                }
            }

            $value = Request::getInt('user_posts_more', 0);
            if (!empty($value)) {
                $criteria->add(new Criteria('posts', $value, '>'));
                $requete_pagenav .= '&amp;user_posts_more=' . $value;
                $requete_search .= 'posts plus de : ' . $value . '<br />';
            }

            $value = Request::getInt('user_posts_less', 0);
            if (!empty($value)) {
                $criteria->add(new Criteria('posts', $value, '<'));
                $requete_pagenav .= '&amp;user_posts_less=' . $value;
                $requete_search .= 'post moins de : ' . $value . '<br />';
            }

            $value = Request::getWord('user_mailok', '');
            if (!empty($value) && ($value !== 'both')) {
                $ok = ($value === 'mailok') ? 1 : 0;
                $criteria->add(new Criteria('user_mailok', $ok));
                $requete_pagenav .= '&amp;user_mailok=' . $value;
                $requete_search .= 'accept email : ' . $value . '<br />';
            }

            $user_type = Request::getWord('user_type', '');
            if (!empty($user_type) && ($user_type !== 'both')) {
                if ($user_type === 'inactv') {
                    $criteria->add(new Criteria('level', 0, '='));
                } elseif ($user_type === "actv") {
                    $criteria->add(new Criteria('level', 0, '>'));
                }
                $requete_search .= 'actif ou inactif : ' . $user_type . '<br />';
                $requete_pagenav .= '&amp;user_type=' . $user_type;
            }

            //$groups = empty($_REQUEST['selgroups']) ? array() : array_map("intval", $_REQUEST['selgroups']);
            $validsort = array("uname", "email", "last_login", "user_regdate", "posts");
            $sort = Request::getWord('user_sort', 'user_regdate');
            $sort = (!in_array($sort, $validsort)) ? "user_regdate" : $sort;
            $requete_pagenav .= '&amp;user_sort=' . $sort;
            $requete_search .= 'order by : ' . $sort . '<br />';
            $criteria->setSort($sort);

            $order = Request::getWord('user_order', 'DESC');
            $requete_pagenav .= '&amp;user_order=' . $order;
            $requete_search .= 'tris : ' . $order . '<br />';
            $criteria->setOrder($order);

            $user_limit = $xoops->getModuleConfig('users_pager', 'system');
            if (isset($_REQUEST['user_limit'])) {
                $user_limit = $_REQUEST['user_limit'];
                $requete_pagenav .= '&amp;user_limit=' . $myts->htmlSpecialChars($_REQUEST['user_limit']);
                $requete_search .= 'limit : ' . $user_limit . '<br />';
            } else {
                $requete_pagenav .= '&amp;user_limit=' . $xoops->getModuleConfig('users_pager', 'system');
                $requete_search .= 'limit : ' . $user_limit . '<br />';
            }

            $start = (!empty($_REQUEST['start'])) ? (int)($_REQUEST['start']) : 0;

            if (isset($_REQUEST['selgroups'])) {
                if ($_REQUEST['selgroups'] != 0) {
                    if (count($_REQUEST['selgroups']) == 1) {
                        $groups = array(0 => $_REQUEST['selgroups']);
                    } else {
                        $groups = array_map("intval", $_REQUEST['selgroups']);
                    }
                } else {
                    $groups = array();
                }
                $requete_pagenav .= '&amp;selgroups=' . $myts->htmlSpecialChars($_REQUEST['selgroups']);
            } else {
                $groups = array();
            }
            //print_r($groups);
            $member_handler = $xoops->getHandlerMember();
            $users_count = $member_handler->getUserCountByGroupLink($groups, $criteria);
            $users_arr = array();
            if ($start < $users_count) {
                echo sprintf(XoopsLocale::F_USERS_FOUND, $users_count) . "<br />";
                $criteria->setSort($sort);
                $criteria->setOrder($order);
                $criteria->setLimit($user_limit);
                $criteria->setStart($start);
                $users_arr = $member_handler->getUsersByGroupLink($groups, $criteria, true);
                $ucount = 0;
            }

            $xoops->tpl()->assign('users_count', $users_count);
            $xoops->tpl()->assign('users_display', true);
            $xoops->tpl()->assign('php_selft', $_SERVER['PHP_SELF']);
            $xoops->tpl()->assign('token', $xoops->security()->getTokenHTML());
            $xoops->tpl()->assign('token_id', $xoops->security()->createToken());

            //User limit
            //$user_limit = (!isset($_REQUEST['user_limit'])) ? 20 : $_REQUEST['user_limit'];
            //User type
            //$user_type = (!isset($_REQUEST['user_type'])) ? '' : $_REQUEST['user_type'];
            //selgroups
            $selgroups = (!isset($_REQUEST['selgroups'])) ? '' : $_REQUEST['selgroups'];

            $user_uname = (!isset($_REQUEST['user_uname'])) ? '' : $_REQUEST['user_uname'];
            //Form tris
            $form = '<form action="admin.php?fct=users" method="post">
                    ' . SystemLocale::C_SEARCH_USER . '<input type="text" name="user_uname" value="' . $myts->htmlSpecialChars($user_uname) . '" size="15">
                    <select name="selgroups">
                        <option value="" selected="selected">' . XoopsLocale::ALL_GROUPS . '</option>';
            $group_handler = $xoops->getHandlerGroup();
            $group_arr = $group_handler->getObjects();
            /* @var $group XoopsGroup */
            foreach ($group_arr as $group) {
                if ($group->getVar("groupid") != FixedGroups::ANONYMOUS) {
                    $form .= '<option value="' . $group->getVar("groupid") . '"  ' . ($selgroups == $group->getVar("groupid") ? ' selected="selected"' : '') . '>' . $group->getVar("name") . '</option>';
                }
            }
            unset($group);
            $form .= '</select>&nbsp;
                <select name="user_type">
                    <option value="" ' . ($user_type == '' ? ' selected="selected"' : '') . '>' . XoopsLocale::ALL_USERS . '</option>
                    <option value="actv" ' . ($user_type === 'actv' ? ' selected="selected"' : '') . '>' . SystemLocale::ONLY_ACTIVE_USERS . '</option>
                    <option value="inactv" ' . ($user_type === 'inactv' ? ' selected="selected"' : '') . '>' . SystemLocale::ONLY_INACTIVE_USERS . '</option>
                </select>&nbsp;
                <select name="user_limit">
                    <option value="20" ' . ($user_limit == 20 ? ' selected="selected"' : '') . '>20</option>
                    <option value="50" ' . ($user_limit == 50 ? ' selected="selected"' : '') . '>50</option>
                    <option value="100" ' . ($user_limit == 100 ? ' selected="selected"' : '') . '>100</option>
                </select>&nbsp;
                <input type="hidden" name="user_uname_match" value="XOOPS_MATCH_START" />
                <input class="btn" type="submit" value="' . XoopsLocale::A_SEARCH . '" name="speed_search">&nbsp;
                <input class="btn success" type="submit" value="' . XoopsLocale::ADVANCED_SEARCH . '" name="complet_search"></form>
                ';

            //select groupe
            $form_select_groups = '<select  name="selgroups" id="selgroups"   style="display:none;"><option value="">---------</option>';
            //$module_array[0] = _AM_SYSTEM_USERS_COMMENTS_FORM_ALL_MODS;
            $group_handler = $xoops->getHandlerGroup();
            $group_arr = $group_handler->getObjects();
            /* @var $group XoopsGroup */
            foreach ($group_arr as $group) {
                if ($group->getVar("groupid") != FixedGroups::ANONYMOUS) {
                    $form_select_groups .= '<option value="' . $group->getVar("groupid") . '"  ' . ($selgroups == $group->getVar("groupid") ? ' selected="selected"' : '') . '>' . $group->getVar("name") . '</option>';
                }
            }
            unset($group);
            $form_select_groups .= '</select><input type="hidden" name="op" value="users_add_delete_group">';

            $xoops->tpl()->assign('form_sort', $form);
            $xoops->tpl()->assign('form_select_groups', $form_select_groups);
            //echo $requete_search;
            if ($users_count > 0) {
                //echo $requete_search;
                $ListOfAdmins = $member_handler->getUsersByGroup(FixedGroups::ADMIN);
                /* @var $user XoopsUser */
                foreach ($users_arr as $user) {
                    $users['uid'] = $user->getVar("uid");
                    //Display group
                    if (in_array($users['uid'], $ListOfAdmins)) {
                        $users['group'] = system_AdminIcons('xoops/group_1.png');
                        //$users['icon'] = '<img src="'.\XoopsBaseConfig::get('url').'/modules/system/images/icons/admin.png" alt="'._AM_SYSTEM_USERS_ADMIN.'" title="'._AM_SYSTEM_USERS_ADMIN.'" />';
                        $users['checkbox_user'] = false;
                    } else {
                        $users['group'] = system_AdminIcons('xoops/group_2.png');
                        //$users['icon'] = '<img src="'.\XoopsBaseConfig::get('url').'/modules/system/images/icons/user.png" alt="'._AM_SYSTEM_USERS_USER.'" title="'._AM_SYSTEM_USERS_USER.'" />';
                        $users['checkbox_user'] = true;
                    }
                    $users['name'] = $user->getVar("uid");
                    $users['name'] = $user->getVar("name");
                    $users['uname'] = $user->getVar("uname");
                    $users['email'] = $user->getVar("email");
                    $users['url'] = $user->getVar("url");
                    $avatar = $xoops->service('avatar')->getAvatarUrl($user)->getValue();
                    $users['user_avatar'] = (empty($avatar) ? system_AdminIcons('anonymous.png') : $avatar);
                    $users['reg_date'] = XoopsLocale::formatTimestamp($user->getVar("user_regdate"), "m");
                    if ($user->getVar("last_login") > 0) {
                        $users['last_login'] = XoopsLocale::formatTimestamp($user->getVar("last_login"), "m");
                    } else {
                        $users['last_login'] = SystemLocale::NEVER_CONNECTED;
                    }
                    $users['user_level'] = $user->getVar("level");
                    $users['user_icq'] = $user->getVar("user_icq");
                    $users['user_aim'] = $user->getVar("user_aim");
                    $users['user_yim'] = $user->getVar("user_yim");
                    $users['user_msnm'] = $user->getVar("user_msnm");

                    $users['posts'] = $user->getVar("posts");

                    $xoops->tpl()->appendByRef('users', $users);
                    $xoops->tpl()->appendByRef('users_popup', $users);
                    unset($users, $user);
                }
            } else {
                $xoops->tpl()->assign('users_no_found', true);
            }

            if ($users_count > $user_limit) {
                $nav = new XoopsPageNav($users_count, $user_limit, $start, 'start', 'fct=users&amp;op=default' . $requete_pagenav);
                $xoops->tpl()->assign('nav', $nav->renderNav());
            }
        }
        break;
}
// Call Footer
$xoops->footer();

/**
 * addCriteria - add a criteria for a column enforcing XOOPS_MATCH_* rules
 *
 * @param CriteriaCompo $criteria A CriteriaCompo object to add to
 * @param string        $column   column name
 * @param int|string    $value    column value
 * @param integer       $match    A XOOPS_MATCH_* value
 *
 * @return void
 */
function addCriteria(CriteriaCompo $criteria, $column, $value, $match)
{
    $relation = 'LIKE';
    switch ($match) {
        default:
        case XOOPS_MATCH_START:
            $value = $value . '%';
            break;
        case XOOPS_MATCH_END:
            $value = '%' . $value;
            break;
        case XOOPS_MATCH_EQUAL:
            //$value = $value;
            $relation = '=';
            break;
        case XOOPS_MATCH_CONTAIN:
            $value = '%' . $value . '%';
            break;
    }
    $criteria->add(new Criteria($column, $value, $relation));
}
