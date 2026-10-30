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

if (!$xoops->isUser()) {
    $xoops->redirect(\XoopsBaseConfig::get('url'), 2, XoopsLocale::E_NO_ACCESS_PERMISSION);
}

// see https://github.com/dropbox/zxcvbn
$zxcvbn_path = $xoops->url('modules/profile/assets/js/zxcvbn.js');
$zxcvbn =<<<EOT
(function(){
    var a;
    a=function(){
        var a,b;
        b=document.createElement("script");
        b.src="$zxcvbn_path";
        b.type="text/javascript";
        b.async=!0;
        a=document.getElementsByTagName("script")[0];
        return a.parentNode.insertBefore(b,a)
    };
    null!=window.attachEvent?window.attachEvent("onload",a):window.addEventListener("load",a,!1)
}).call(this);
$(document).ready(function(){
    $('#crack_time').addClass('label label-danger label-important');
    $('#crack_time').html("cracktime : instant");
    $('#submit').attr('disabled','disabled');
    $('#newpass').keyup(function() {
        var weak = 259200; // 3 days in seconds
        var good = 7.889e+6; // 3 months in seconds
        var great = 9.467e+7; // 3 years in seconds
        var textValue = $(this).val();
        var result = zxcvbn(textValue);
        $('#crack_time').html("cracktime : " + result.crack_time_display);
        if (result.crack_time < weak) {
            $('#crack_time').removeClass('label-warning label-info label-success');
            $('#crack_time').addClass('label-danger label-important');
            $('#submit').attr('disabled','disabled');
        }
        if (result.crack_time >= weak && result.crack_time < good) {
            $('#crack_time').removeClass('label-danger label-important label-info label-success');
            $('#crack_time').addClass('label-warning');
            $('#submit').attr('disabled','disabled');
        }
        if (result.crack_time >= good) {
            $('#crack_time').removeClass('label-danger label-important label-warning label-success');
            $('#crack_time').addClass('label-info');
            $('#submit').removeAttr('disabled');
        }
        if (result.crack_time >= great) {
            $('#crack_time').removeClass('label-danger label-important label-warning label-info');
            $('#crack_time').addClass('label-success');
            $('#submit').removeAttr('disabled');
        }
    });
});
EOT;

$xoops->header('module:profile/profile_changepass.tpl');

if (!isset($_POST['submit'])) {
    $xoops->theme()->addScript(null, array('type' => 'application/x-javascript'), $zxcvbn);
    //show change password form
    $form = new Xoops\Form\ThemeForm(_PROFILE_MA_CHANGEPASSWORD, 'form', $_SERVER['REQUEST_URI'], 'post', true);
    $form->addElement(new Xoops\Form\Password(_PROFILE_MA_OLDPASSWORD, 'oldpass'), true);
    $password = new Xoops\Form\Password(_PROFILE_MA_NEWPASSWORD, 'newpass', null, null, '', 'off', 'New Password');
    $password->setPattern('^.{8,}$', 'You need at least 8 characters');
    $form->addElement($password, true);
    $form->addElement(new Xoops\Form\Label(XoopsLocale::PASSWORD_STRENGTH, '', 'crack_time'));
    $form->addElement(new Xoops\Form\Password(XoopsLocale::VERIFY_PASSWORD, 'vpass'), true);
    $form->addElement(new Xoops\Form\Button('', 'submit', XoopsLocale::A_SUBMIT, 'submit'));
    $form->assign($xoops->tpl());
    $xoops->registry()->get('profile_breadcrumbs')->addItem(
        new Link(['caption' => _PROFILE_MA_CHANGEPASSWORD])
    );

} else {
    $xoops->getConfigs();
    $myts = \Xoops\Core\Text\Sanitizer::getInstance();
    $oldpass = trim($_POST['oldpass']);
    $password = trim($_POST['newpass']);
    $vpass = trim($_POST['vpass']);
    $errors = array();
    if (!$xoops->getHandlerMember()->verifyPassword($xoops->user, $oldpass)) {
        $errors[] = _PROFILE_MA_WRONGPASSWORD;
    }
    if (mb_strlen($password) < $xoops->getConfig('minpass')) {
        $errors[] = sprintf(XoopsLocale::EF_PASSWORD_MUST_BE_GREATER_THAN, $xoops->getConfig('minpass'));
    }
    if ($password != $vpass) {
        $errors[] = XoopsLocale::E_PASSWORDS_MUST_MATCH;
    }

    if ($errors) {
        $msg = implode('<br />', $errors);
    } else {
        //update password
        $xoops->user->setVar('pass', password_hash($password, PASSWORD_DEFAULT));
        $xoops->user->setVar('last_pass_change', time());
        if ($xoops->getHandlerMember()->insertUser($xoops->user)) {
            (new \Xoops\Core\Session\RememberMe())->invalidateAllForUser($xoops->user->getVar('uid'));
            $msg = _PROFILE_MA_PASSWORDCHANGED;
        } else {
            $msg = _PROFILE_MA_ERRORDURINGSAVE;
        }
    }
    $xoops->redirect(
        \XoopsBaseConfig::get('url') . '/modules/' . $xoops->module->getVar('dirname', 'n') . '/userinfo.php?uid='
        . $xoops->user->getVar('uid'),
        2,
        $msg
    );
}

include __DIR__ . '/footer.php';
