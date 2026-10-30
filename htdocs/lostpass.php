<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

use Xmf\Request;

/**
 * XOOPS password recovery
 *
 * @copyright       XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         core
 * @since           2.0.0
 * @version         $Id$
 */

include __DIR__ . '/mainfile.php';
$xoops = Xoops::getInstance();
$xoops->events()->triggerEvent('core.lostpass.start');

$xoops_url = \XoopsBaseConfig::get('url');

$xoops->loadLanguage('user');

$email = Request::getEmail('email', null, 'GET');
$email = Request::getEmail('email', $email, 'POST');

if (empty($email)) {
    $xoops->simpleRedirect('user.php');
    exit();
}

$userHandler = Xoops::getInstance()->getHandlerUser();
$getuser = $userHandler->getObjects(new Criteria('email', $email));

if (empty($getuser)) {
    $msg = XoopsLocale::E_NO_USER_FOUND;
    $xoops->redirect("user.php", 2, $msg);
} else {
    $userObject = $getuser[0]; // what if there was more than one?
    $code = Request::getCmd('code', '', 'GET');
    // full digest: the old 5-character code could be guessed; the hash changes once used
    $areyou = md5($userObject->getVar("pass", 'n'));
    if ($code != '' && hash_equals($areyou, $code)) {
        $newpass = $xoops->makePass();
        // save first: never mail a password that was not stored
        $userObject->setVar("pass", password_hash($newpass, PASSWORD_DEFAULT));
        $userObject->setVar('last_pass_change', time());
        if (false === $userHandler->insert($userObject)) {
            $xoops->header();
            echo XoopsLocale::E_USER_NOT_UPDATED;
            $xoops->footer();
            exit();
        }
        (new \Xoops\Core\Session\RememberMe())->invalidateAllForUser($userObject->getVar('uid'));
        $xoopsMailer = $xoops->getMailer();
        $xoopsMailer->useMail();
        $xoopsMailer->setTemplate("lostpass2.tpl");
        $xoopsMailer->assign("SITENAME", $xoops->getConfig('sitename'));
        $xoopsMailer->assign("ADMINMAIL", $xoops->getConfig('adminmail'));
        $xoopsMailer->assign("SITEURL", $xoops_url . "/");
        $xoopsMailer->assign("IP", $_SERVER['REMOTE_ADDR']);
        $xoopsMailer->assign("NEWPWD", $newpass);
        $xoopsMailer->setToUsers($userObject);
        $xoopsMailer->setFromEmail($xoops->getConfig('adminmail'));
        $xoopsMailer->setFromName($xoops->getConfig('sitename'));
        $xoopsMailer->setSubject(sprintf(XoopsLocale::F_NEW_PASSWORD_REQUEST_AT, \XoopsBaseConfig::get('url')));
        if (!$xoopsMailer->send()) {
            echo $xoopsMailer->getErrors();
        }
        $xoops->redirect("user.php", 3, sprintf(XoopsLocale::SF_PASSWORD_SENT_TO, $userObject->getVar("uname")), false);
        // If no Code, send it
    } else {
        $xoopsMailer = $xoops->getMailer();
        $xoopsMailer->useMail();
        $xoopsMailer->setTemplate("lostpass1.tpl");
        $xoopsMailer->assign("SITENAME", $xoops->getConfig('sitename'));
        $xoopsMailer->assign("ADMINMAIL", $xoops->getConfig('adminmail'));
        $xoopsMailer->assign("SITEURL", $xoops_url . "/");
        $xoopsMailer->assign("IP", $_SERVER['REMOTE_ADDR']);
        $xoopsMailer->assign("NEWPWD_LINK", $xoops_url . "/lostpass.php?email=" . urlencode($email) . "&code=" . $areyou);
        $xoopsMailer->setToUsers($userObject);
        $xoopsMailer->setFromEmail($xoops->getConfig('adminmail'));
        $xoopsMailer->setFromName($xoops->getConfig('sitename'));
        $xoopsMailer->setSubject(sprintf(XoopsLocale::F_NEW_PASSWORD_REQUEST_AT, $xoops->getConfig('sitename')));
        $xoops->header();
        if (!$xoopsMailer->send()) {
            echo $xoops->alert('error', $xoopsMailer->getErrors(false));
        } else {
            echo $xoops->alert('success', sprintf(XoopsLocale::F_CONFIRMATION_EMAIL_SENT, $userObject->getVar("uname")));
        }
        $xoops->footer();
    }
}
