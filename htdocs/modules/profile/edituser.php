<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

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

// If not a user, redirect
if (!$xoops->isUser()) {
    $xoops->redirect(\XoopsBaseConfig::get('url'), 3, XoopsLocale::E_NO_ACTION_PERMISSION);
}

$myts = \Xoops\Core\Text\Sanitizer::getInstance();
$op = isset($_REQUEST['op']) ? $_REQUEST['op'] : 'editprofile';
$xoops->getConfigs();

if ($op === 'save') {
    if (!$xoops->security()->check()) {
        $xoops->redirect(\XoopsBaseConfig::get('url') . "/modules/" . $xoops->module->getVar('dirname', 'n') . "/", 3, XoopsLocale::E_NO_ACTION_PERMISSION . "<br />" . implode('<br />', $xoops->security()->getErrors()));
        exit();
    }
    $uid = $xoops->user->getVar('uid');
    $errors = array();
    $edituser = $xoops->user;
    $wrongPassword = false;
    if ($xoops->user->isAdmin()) {
        $email = trim($_POST['email']);
        // a new email address needs the current password
        $wrongPassword = $email !== $edituser->getVar('email', 'n')
            && !$xoops->getHandlerMember()->verifyPassword($edituser, isset($_POST['oldpass']) ? (string) $_POST['oldpass'] : '');
        $edituser->setVar('uname', trim($_POST['uname']));
        if (!$wrongPassword) {
            $edituser->setVar('email', $email);
        }
    }
    $stop = XoopsUserUtility::validate($edituser);
    if ($wrongPassword) {
        $stop .= XoopsLocale::E_WRONG_CURRENT_PASSWORD . '<br />';
    }

    if (!empty($stop)) {
        $op = 'editprofile';
    } else {

        // Dynamic fields
        /* @var $profile_handler ProfileProfileHandler */
        $profile_handler = \Xoops::getModuleHelper('profile')->getHandler('profile');
        // Get fields
        $fields = $profile_handler->loadFields();
        // Get ids of fields that can be edited
        $gperm_handler = $xoops->getHandlerGroupPermission();
        $editable_fields = $gperm_handler->getItemIds('profile_edit', $xoops->user->getGroups(), $xoops->module->getVar('mid'));

        if (!$profile = $profile_handler->getProfile($edituser->getVar('uid'))) {
            $profile = $profile_handler->create();
            $profile->setVar('profile_id', $edituser->getVar('uid'));
        }

        /* @var ProfileField $field */
        foreach ($fields as $field) {
            $fieldname = $field->getVar('field_name');
            if (in_array($field->getVar('field_id'), $editable_fields) && isset($_REQUEST[$fieldname])) {
                $value = $field->getValueForSave($_REQUEST[$fieldname]);
                if (in_array($fieldname, $profile_handler->getUserVars())) {
                    $edituser->setVar($fieldname, $value);
                } else {
                    $profile->setVar($fieldname, $value);
                }
            }
        }
        if (!$member_handler->insertUser($edituser)) {
            $stop = $edituser->getHtmlErrors();
            $op = 'editprofile';
        } else {
            $profile->setVar('profile_id', $edituser->getVar('uid'));
            $profile_handler->insert($profile);
            unset($_SESSION['xoopsUserTheme']);
            $xoops->redirect(\XoopsBaseConfig::get('url') . '/modules/' . $xoops->module->getVar('dirname', 'n') . '/userinfo.php?uid=' . $edituser->getVar('uid'), 2, XoopsLocale::S_YOUR_PROFILE_UPDATED);
        }
    }
}

if ($op === 'editprofile') {
    $xoops->header('module:profile/profile_editprofile.tpl');
    include_once __DIR__ . '/include/forms.php';
    $form = profile_getUserForm($xoops->user);
    $form->assign($xoops->tpl());
    if (!empty($stop)) {
        $xoops->tpl()->assign('stop', $xoops->alert('error', $stop));
    }

    $xoops->registry()->get('profile_breadcrumbs')->addItem(
        new Link(['caption' => XoopsLocale::EDIT_PROFILE])
    );
}
include __DIR__ . '/footer.php';
