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
 *  Xoops Edit User
 *
 * @copyright       XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         core
 * @since           2.0.0
 */

use Xmf\Request;
use Xoops\Core\Session\RememberMe;

include __DIR__ . '/mainfile.php';

$xoops = Xoops::getInstance();
$xoops->events()->triggerEvent('core.edituser.start');
$xoops->loadLanguage('user');

// If not a user, redirect
if (!$xoops->isUser()) {
    $xoops->redirect('index.php', 3, XoopsLocale::E_NO_ACTION_PERMISSION);
    exit();
}

// initialize $op variable
$op = Request::getCmd('op', 'editprofile');

$myts = \Xoops\Core\Text\Sanitizer::getInstance();
if ($op === 'saveuser') {
    if (!$xoops->security()->check()) {
        $xoops->redirect(
            'index.php',
            3,
            XoopsLocale::E_NO_ACTION_PERMISSION . "<br />" . implode('<br />', $xoops->security()->getErrors())
        );
        exit();
    }
    $uid = Request::getInt('uid', 0);
    if (empty($uid) || $xoops->user->getVar('uid') != $uid) {
        $xoops->redirect('index.php', 3, XoopsLocale::E_NO_ACTION_PERMISSION);
        exit();
    }
    $errors = array();
    $email='';
    if ($xoops->getConfig('allow_chgmail') == 1) {
        $email = Request::getString('email', '');
        if ($email == '' || ! $xoops->checkEmail($email)) {
            $errors[] = XoopsLocale::E_INVALID_EMAIL;
        }
    }
    $password = Request::getString('password', '');
    if ($password != '') {
        if (mb_strlen($password) < $xoops->getConfig('minpass')) {
            $errors[] = sprintf(XoopsLocale::EF_PASSWORD_MUST_BE_GREATER_THAN, $xoops->getConfig('minpass'));
        }
        $vpass = Request::getString('vpass', '');
        if ($password != $vpass) {
            $errors[] = XoopsLocale::E_PASSWORDS_MUST_MATCH;
        }
    }
    // a new password or email address needs the current password
    $emailChanged = $email != '' && $email != $xoops->user->getVar('email', 'n');
    if ($password != '' || $emailChanged) {
        $oldpass = Request::getString('oldpass');
        if (!$xoops->getHandlerMember()->verifyPassword($xoops->user, $oldpass)) {
            $errors[] = XoopsLocale::E_WRONG_CURRENT_PASSWORD;
        }
    }
    if (count($errors) > 0) {
        $xoops->header();
        echo '<div>';
        foreach ($errors as $er) {
            echo '<span class="red bold">' . $er . '</span><br />';
        }
        echo '</div><br />';
        $op = 'editprofile';
    } else {
        $member_handler = $xoops->getHandlerMember();
        $edituser = $member_handler->getUser($uid);
        $edituser->setVar('name', Request::getString('name', ''));
        if ($xoops->getConfig('allow_chgmail') == 1) {
            $edituser->setVar('email', $email);
        }
        if ($password != '') {
            $edituser->setVar('pass', password_hash($password, PASSWORD_DEFAULT));
            $edituser->setVar('last_pass_change', time());
        }
        $edituser->setVar('url', $xoops->formatURL(Request::getUrl('url', '')));
        $edituser->setVar('user_icq', Request::getString('user_icq', ''));
        $edituser->setVar('user_from', Request::getString('user_from', ''));
        $edituser->setVar('user_sig', XoopsLocale::substr(Request::getString('user_sig', ''), 0, 255));
        $edituser->setVar('user_viewemail', Request::getBool('user_viewemail', 0));
        $edituser->setVar('user_aim', Request::getString('user_aim', ''));
        $edituser->setVar('user_yim', Request::getString('user_yim', ''));
        $edituser->setVar('user_msnm', Request::getString('user_msnm', ''));
        $edituser->setVar('attachsig', Request::getBool('attachsig', 0));
        $edituser->setVar('timezone', Request::getString('timezone', 'UTC'));
        $edituser->setVar('uorder', Request::getInt('uorder', 0));
        $edituser->setVar('umode', Request::getString('umode', 'flat'));
        $edituser->setVar('notify_method', Request::getInt('notify_method', 1));
        $edituser->setVar('notify_mode', Request::getInt('notify_mode', 1));
        $edituser->setVar('bio', XoopsLocale::substr(Request::getString('bio', ''), 0, 255));
        $edituser->setVar('user_occ', Request::getString('user_occ', ''));
        $edituser->setVar('user_intrest', Request::getString('user_intrest', ''));
        $edituser->setVar('user_mailok', Request::getBool('user_mailok', 0));
        if (! $member_handler->insertUser($edituser)) {
            $xoops->header();
            echo $edituser->getHtmlErrors();
            $xoops->footer();
        } else {
            if ($password != '') {
                new RememberMe()->invalidateAllForUser($uid);
            }
            $xoops->redirect('userinfo.php?uid=' . $uid, 1, XoopsLocale::S_YOUR_PROFILE_UPDATED);
        }
        exit();
    }
}

if ($op === 'editprofile') {
    $xoops->header('module:system/system_edituser.tpl');
    $xoops->tpl()->assign('uid', $xoops->user->getVar("uid"));
    $xoops->tpl()->assign('editprofile', true);
    $form = new Xoops\Form\ThemeForm(XoopsLocale::EDIT_PROFILE, 'userinfo', 'edituser.php', 'post', true);
    $uname_label = new Xoops\Form\Label(XoopsLocale::USERNAME, $xoops->user->getVar('uname'));
    $form->addElement($uname_label);
    $name_text = new Xoops\Form\Text(XoopsLocale::REAL_NAME, 'name', 30, 60, $xoops->user->getVar('name', 'E'));
    $form->addElement($name_text);
    $email_tray = new Xoops\Form\ElementTray(XoopsLocale::EMAIL, '<br />');
    if ($xoops->getConfig('allow_chgmail') == 1) {
        $email_text = new Xoops\Form\Text('', 'email', 30, 60, $xoops->user->getVar('email'));
    } else {
        $email_text = new Xoops\Form\Label('', $xoops->user->getVar('email'));
    }
    $email_tray->addElement($email_text, ($xoops->getConfig('allow_chgmail') == 1));
    $email_cbox_value = $xoops->user->user_viewemail() ? 1 : 0;
    $email_cbox = new Xoops\Form\Checkbox('', 'user_viewemail', $email_cbox_value);
    $email_cbox->addOption(1, XoopsLocale::ALLOW_OTHER_USERS_TO_VIEW_EMAIL);
    $email_tray->addElement($email_cbox);
    $form->addElement($email_tray);
    $url_text = new Xoops\Form\Text(XoopsLocale::WEBSITE, 'url', 30, 100, $xoops->user->getVar('url', 'E'));
    $form->addElement($url_text);

    $timezone_select = new Xoops\Form\SelectTimeZone(
        XoopsLocale::TIME_ZONE,
        'timezone',
        $xoops->user->getVar('timezone')
    );
    $icq_text = new Xoops\Form\Text(XoopsLocale::ICQ, 'user_icq', 15, 15, $xoops->user->getVar('user_icq', 'E'));
    $aim_text = new Xoops\Form\Text(XoopsLocale::AIM, 'user_aim', 18, 18, $xoops->user->getVar('user_aim', 'E'));
    $yim_text = new Xoops\Form\Text(XoopsLocale::YIM, 'user_yim', 25, 25, $xoops->user->getVar('user_yim', 'E'));
    $msnm_text = new Xoops\Form\Text(XoopsLocale::MSNM, 'user_msnm', 30, 100, $xoops->user->getVar('user_msnm', 'E'));
    $location_text = new Xoops\Form\Text(
        XoopsLocale::LOCATION,
        'user_from',
        30,
        100,
        $xoops->user->getVar('user_from', 'E')
    );
    $occupation_text = new Xoops\Form\Text(
        XoopsLocale::OCCUPATION,
        'user_occ',
        30,
        100,
        $xoops->user->getVar('user_occ', 'E')
    );
    $interest_text = new Xoops\Form\Text(
        XoopsLocale::INTEREST,
        'user_intrest',
        30,
        150,
        $xoops->user->getVar('user_intrest', 'E')
    );
    //$sig_tray = new Xoops\Form\ElementTray(XoopsLocale::SIGNATURE, '<br />');
    $sig_tarea = new Xoops\Form\DhtmlTextArea(XoopsLocale::SIGNATURE, 'user_sig', $xoops->user->getVar('user_sig', 'E'));
    //$sig_tray->addElement($sig_tarea);
    $sig_cbox_value = $xoops->user->getVar('attachsig') ? 1 : 0;
    $sig_cbox = new Xoops\Form\Checkbox('', 'attachsig', $sig_cbox_value);
    $sig_cbox->addOption(1, XoopsLocale::ALWAYS_ATTACH_MY_SIGNATURE);
    //$sig_tray->addElement($sig_cbox);
    $bio_tarea = new Xoops\Form\TextArea(XoopsLocale::EXTRA_INFO, 'bio', $xoops->user->getVar('bio', 'E'));
    $pwd_text = new Xoops\Form\Password('', 'password');
    $pwd_text2 = new Xoops\Form\Password('', 'vpass');
    $pwd_tray = new Xoops\Form\ElementTray(
        XoopsLocale::PASSWORD . '<br />' . XoopsLocale::TYPE_NEW_PASSWORD_TWICE_TO_CHANGE_IT
    );
    $pwd_tray->addElement($pwd_text);
    $pwd_tray->addElement($pwd_text2);
    $oldpass_text = new Xoops\Form\Password(XoopsLocale::CURRENT_PASSWORD, 'oldpass');
    $oldpass_text->setDescription(XoopsLocale::CURRENT_PASSWORD_DESC);
    $mailok_radio = new Xoops\Form\RadioYesNo(
        XoopsLocale::Q_RECEIVE_OCCASIONAL_EMAIL_NOTICES_FROM_ADMINISTRATORS,
        'user_mailok',
        $xoops->user->getVar('user_mailok')
    );
    $uid_hidden = new Xoops\Form\Hidden('uid', $xoops->user->getVar('uid'));
    $op_hidden = new Xoops\Form\Hidden('op', 'saveuser');
    $submit_button = new Xoops\Form\Button('', 'submit', XoopsLocale::SAVE_CHANGES, 'submit');

    $form->addElement($timezone_select);
    $form->addElement($icq_text);
    $form->addElement($aim_text);
    $form->addElement($yim_text);
    $form->addElement($msnm_text);
    $form->addElement($location_text);
    $form->addElement($occupation_text);
    $form->addElement($interest_text);
    //$form->addElement($sig_tray);
    $form->addElement($sig_tarea);
    $form->addElement($sig_cbox);
    $form->addElement($bio_tarea);
    $form->addElement($pwd_tray);
    $form->addElement($oldpass_text);
    $form->addElement($mailok_radio);
    $form->addElement($uid_hidden);
    $form->addElement($op_hidden);
    //$form->addElement($token_hidden);
    $form->addElement($submit_button);
    $form->display();
    $xoops->footer();
}
