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
$xoops->header();

$indexAdmin = new \Xoops\Module\Admin();
$indexAdmin->displayNavigation('user.php');

$op = isset($_REQUEST['op']) ? $_REQUEST['op'] : 'list';
if ($op === "editordelete") {
    $op = isset($_REQUEST['delete']) ? "delete" : "edit";
}

$handler = $xoops->getHandlerMember();

switch ($op) {
    default:
    case "list":
        $form = new Xoops\Form\ThemeForm(_PROFILE_AM_EDITUSER, 'form', 'user.php');
        $form->addElement(new Xoops\Form\SelectUser(_PROFILE_AM_SELECTUSER, 'id'));
        $form->addElement(new Xoops\Form\Hidden('op', 'editordelete'));
        $buttonTray = new Xoops\Form\ElementTray('');
        $buttonTray->addElement(new Xoops\Form\Button('', 'edit', XoopsLocale::A_EDIT, 'submit'));
        $buttonTray->addElement(new Xoops\Form\Button('', 'delete', XoopsLocale::A_DELETE, 'submit'));
        $form->addElement($buttonTray);
        $form->display();
        /* fallthrough */

    case "new":
        $xoops->loadLanguage("main", $xoops->module->getVar('dirname', 'n'));
        include_once dirname(__DIR__) . '/include/forms.php';
        $obj = $handler->createUser();
        $obj->setGroups(array(FixedGroups::USERS));
        $form = profile_getUserForm($obj);
        $form->display();
        break;

    case "edit":
        $xoops->loadLanguage("main", $xoops->module->getVar('dirname', 'n'));
        $obj = $handler->getUser($_REQUEST['id']);
        if (in_array(FixedGroups::ADMIN, $obj->getGroups()) && !in_array(FixedGroups::ADMIN, $xoops->user->getGroups())) {
            // If not webmaster trying to edit a webmaster - disallow
            $xoops->redirect("user.php", 3, XoopsLocale::E_NO_ACTION_PERMISSION);
        }
        include_once dirname(__DIR__) . '/include/forms.php';
        $form = profile_getUserForm($obj);
        $form->display();
        break;

    case "save":
        $xoops->loadLanguage("main", $xoops->module->getVar('dirname', 'n'));
        if (!$xoops->security()->check()) {
            $xoops->redirect(
                'user.php',
                3,
                XoopsLocale::E_NO_ACTION_PERMISSION . "<br />"
                . implode('<br />', $xoops->security()->getErrors())
            );
            exit;
        }

        // Dynamic fields
        /* @var $profile_handler ProfileProfileHandler */
        $profile_handler = \Xoops::getModuleHelper('profile')->getHandler('profile');
        // Get fields
        $fields = $profile_handler->loadFields();
        $userfields = $profile_handler->getUserVars();
        // Get ids of fields that can be edited
        $gperm_handler = $xoops->getHandlerGroupPermission();
        $editable_fields = $gperm_handler->getItemIds(
            'profile_edit',
            $xoops->user->getGroups(),
            $xoops->module->getVar('mid')
        );

        $uid = empty($_POST['uid']) ? 0 : (int)($_POST['uid']);
        if (!empty($uid)) {
            $user = $handler->getUser($uid);
            if (!is_object($user)) {
                $xoops->redirect("user.php", 3, XoopsLocale::E_NO_ACTION_PERMISSION);
            }
            // same rule as the edit form: only a webmaster may change a webmaster
            if (in_array(FixedGroups::ADMIN, $user->getGroups())
                && !in_array(FixedGroups::ADMIN, $xoops->user->getGroups())
            ) {
                $xoops->redirect("user.php", 3, XoopsLocale::E_NO_ACTION_PERMISSION);
            }
            $profile = $profile_handler->getProfile($uid);
            if (!is_object($profile)) {
                $profile = $profile_handler->create();
                $profile->setVar('profile_id', $uid);
            }
        } else {
            $user = $handler->createUser();
            $profile = $profile_handler->create();
            if (count($fields) > 0) {
                foreach (array_keys($fields) as $i) {
                    $fieldname = $fields[$i]->getVar('field_name');
                    if (in_array($fieldname, $userfields)) {
                        $default = $fields[$i]->getVar('field_default');
                        if ($default === '' || $default === null) {
                            continue;
                        }
                        $user->setVar($fieldname, $default);
                    }
                }
            }
            $user->setVar('user_regdate', time());
            $user->setVar('level', 1);
        }
        $myts = \Xoops\Core\Text\Sanitizer::getInstance();
        $user->setVar('uname', $_POST['uname']);
        // changing your own email address needs the current password
        $email = trim($_POST['email']);
        $wrongPassword = !$user->isNew() && $user->getVar('uid') == $xoops->user->getVar('uid')
            && $email !== $user->getVar('email', 'n')
            && !$xoops->getHandlerMember()->verifyPassword($user, isset($_POST['oldpass']) ? (string) $_POST['oldpass'] : '');
        if (!$wrongPassword) {
            $user->setVar('email', $email);
        }
        if (isset($_POST['level']) && $user->getVar('level') != (int)($_POST['level'])) {
            $user->setVar('level', (int)($_POST['level']));
        }
        $password = $vpass = null;
        if (!empty($_POST['password'])) {
            $password = trim($_POST['password']);
            $vpass = trim($_POST['vpass']);
            $user->setVar('pass', password_hash($password, PASSWORD_DEFAULT));
            $user->setVar('last_pass_change', time());
        } elseif ($user->isNew()) {
            $password = $vpass = '';
        }
        $stop = XoopsUserUtility::validate($user, $password, $vpass);

        $errors = array();
        if ($stop != "") {
            $errors[] = $stop;
        }
        if ($wrongPassword) {
            $errors[] = XoopsLocale::E_WRONG_CURRENT_PASSWORD;
        }

        foreach (array_keys($fields) as $i) {
            $fieldname = $fields[$i]->getVar('field_name');
            if (in_array($fields[$i]->getVar('field_id'), $editable_fields) && isset($_REQUEST[$fieldname])) {
                if (in_array($fieldname, $userfields)) {
                    $value = $fields[$i]->getValueForSave($_REQUEST[$fieldname], $user->getVar($fieldname, 'n'));
                    $user->setVar($fieldname, $value);
                } else {
                    $value = $fields[$i]->getValueForSave((isset($_REQUEST[$fieldname]) ? $_REQUEST[$fieldname]
                                : ""), $profile->getVar($fieldname, 'n'));
                    $profile->setVar($fieldname, $value);
                }
            }
        }

        $new_groups = isset($_POST['groups']) ? $_POST['groups'] : array();

        if (count($errors) == 0) {
            if ($handler->insertUser($user)) {
                if (!empty($password)) {
                    (new \Xoops\Core\Session\RememberMe())->invalidateAllForUser($user->getVar('uid'));
                }
                $profile->setVar('profile_id', $user->getVar('uid'));
                $profile_handler->insert($profile);
                include_once $xoops->path("/modules/system/constants.php");
                if ($gperm_handler->checkRight("system_admin", XOOPS_SYSTEM_GROUP, $xoops->user->getGroups(), 1)) {
                    //Update group memberships
                    $cur_groups = $user->getGroups();

                    $new_groups = array_map('intval', (array) $new_groups);
                    $added_groups = array_diff($new_groups, $cur_groups);
                    $removed_groups = array_diff($cur_groups, $new_groups);
                    // only a webmaster may grant or revoke webmaster membership
                    if (!in_array(FixedGroups::ADMIN, $xoops->user->getGroups())) {
                        $added_groups = array_diff($added_groups, array(FixedGroups::ADMIN));
                        $removed_groups = array_diff($removed_groups, array(FixedGroups::ADMIN));
                    }

                    if (count($added_groups) > 0) {
                        foreach ($added_groups as $groupid) {
                            $handler->addUserToGroup($groupid, $user->getVar('uid'));
                        }
                    }
                    if (count($removed_groups) > 0) {
                        foreach ($removed_groups as $groupid) {
                            $handler->removeUsersFromGroup($groupid, array($user->getVar('uid')));
                        }
                    }
                }
                if ($user->isNew()) {
                    $xoops->redirect('user.php', 2, _PROFILE_AM_USERCREATED, false);
                } else {
                    $xoops->redirect('user.php', 2, XoopsLocale::S_YOUR_PROFILE_UPDATED, false);
                }
            }
        } else {
            foreach ($errors as $err) {
                $user->setErrors($err);
            }
        }
        $user->setGroups($new_groups);
        include_once dirname(__DIR__) . '/include/forms.php';
        echo $user->getHtmlErrors();
        $form = profile_getUserForm($user, $profile);
        $form->display();
        break;

    case "delete":
        if ($_REQUEST['id'] == $xoops->user->getVar('uid')) {
            $xoops->redirect('user.php', 2, _PROFILE_AM_CANNOTDELETESELF);
        }
        $obj = $handler->getUser($_REQUEST['id']);
        $groups = $obj->getGroups();
        if (in_array(FixedGroups::ADMIN, $groups)) {
            $xoops->redirect('user.php', 3, _PROFILE_AM_CANNOTDELETEADMIN, false);
        }

        if (isset($_REQUEST['ok']) && $_REQUEST['ok'] == 1) {
            if (!$xoops->security()->check()) {
                $xoops->redirect('user.php', 3, implode(',', $xoops->security()->getErrors()), false);
            }
            $profile_handler = \Xoops::getModuleHelper('profile')->getHandler('profile');
            $profile = $profile_handler->getProfile($obj->getVar('uid'));
            if (!$profile || $profile->isNew() || $profile_handler->delete($profile)) {
                if ($handler->deleteUser($obj)) {
                    $xoops->redirect(
                        'user.php',
                        3,
                        sprintf(_PROFILE_AM_DELETEDSUCCESS, $obj->getVar('uname') . " (" . $obj->getVar('email') . ")"),
                        false
                    );
                } else {
                    echo $obj->getHtmlErrors();
                }
            } else {
                echo $profile->getHtmlErrors();
            }

        } else {
            echo $xoops->confirm(
                array('ok' => 1, 'id' => $_REQUEST['id'], 'op' => 'delete'),
                $_SERVER['REQUEST_URI'],
                sprintf(_PROFILE_AM_RUSUREDEL, $obj->getVar('uname') . " (" . $obj->getVar('email') . ")")
            );
        }
        break;
}

$xoops->footer();
