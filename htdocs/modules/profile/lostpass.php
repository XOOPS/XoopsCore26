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
 * @author          Jan Pedersen
 * @author          Taiwen Jiang <phppp@users.sourceforge.net>
 */

include __DIR__ . '/header.php';
$xoops = Xoops::getInstance();
$email = isset($_GET['email']) ? trim($_GET['email']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : $email;

if ($email == '') {
    $xoops->redirect("user.php", 2, XoopsLocale::E_NO_USER_FOUND, false);
}

$myts = \Xoops\Core\Text\Sanitizer::getInstance();
$member_handler = $xoops->getHandlerMember();
/* @var $user XoopsUser */
list($user) = $member_handler->getUsers(new Criteria('email', $email));

if (empty($user)) {
    $msg = XoopsLocale::E_NO_USER_FOUND;
    $xoops->redirect("user.php", 2, $msg, false);
} else {
    $code = isset($_GET['code']) ? trim($_GET['code']) : '';
    // full digest of the hash: a prefix of the hash itself is the same for every bcrypt user
    $areyou = md5($user->getVar("pass", 'n'));
    if ($code != '' && hash_equals($areyou, $code)) {
        $newpass = $xoops->makePass();
        // save first: never mail a password that was not stored
        $user->setVar('pass', password_hash($newpass, PASSWORD_DEFAULT));
        $user->setVar('last_pass_change', time());
        if (!$member_handler->insertUser($user)) {
            $xoops->header();
            echo XoopsLocale::E_USER_NOT_UPDATED;
            include __DIR__ . '/footer.php';
            exit();
        }
        (new \Xoops\Core\Session\RememberMe())->invalidateAllForUser($user->getVar('uid'));
        $xoopsMailer = $xoops->getMailer();
        $xoopsMailer->useMail();
        $xoopsMailer->setTemplate("lostpass2.tpl");
        $xoopsMailer->assign("SITENAME", $xoops->getConfig('sitename'));
        $xoopsMailer->assign("ADMINMAIL", $xoops->getConfig('adminmail'));
        $xoopsMailer->assign("SITEURL", \XoopsBaseConfig::get('url') . "/");
        $xoopsMailer->assign("IP", $_SERVER['REMOTE_ADDR']);
        $xoopsMailer->assign("NEWPWD", $newpass);
        $xoopsMailer->setToUsers($user);
        $xoopsMailer->setFromEmail($xoops->getConfig('adminmail'));
        $xoopsMailer->setFromName($xoops->getConfig('sitename'));
        $xoopsMailer->setSubject(sprintf(XoopsLocale::F_NEW_PASSWORD_REQUEST_AT, \XoopsBaseConfig::get('url')));
        if (!$xoopsMailer->send()) {
            echo $xoopsMailer->getErrors();
        }
        $xoops->redirect("user.php", 3, sprintf(XoopsLocale::SF_PASSWORD_SENT_TO, $user->getVar("uname")), false);
        // If no Code, send it
    } else {
        $xoopsMailer = $xoops->getMailer();
        $xoopsMailer->useMail();
        $xoopsMailer->setTemplate("lostpass1.tpl");
        $xoopsMailer->assign("SITENAME", $xoops->getConfig('sitename'));
        $xoopsMailer->assign("ADMINMAIL", $xoops->getConfig('adminmail'));
        $xoopsMailer->assign("SITEURL", \XoopsBaseConfig::get('url') . "/");
        $xoopsMailer->assign("IP", $_SERVER['REMOTE_ADDR']);
        $xoopsMailer->assign("NEWPWD_LINK", \XoopsBaseConfig::get('url') . "/modules/profile/lostpass.php?email=" . urlencode($email) . "&code=" . $areyou);
        $xoopsMailer->setToUsers($user);
        $xoopsMailer->setFromEmail($xoops->getConfig('adminmail'));
        $xoopsMailer->setFromName($xoops->getConfig('sitename'));
        $xoopsMailer->setSubject(sprintf(XoopsLocale::F_NEW_PASSWORD_REQUEST_AT, $xoops->getConfig('sitename')));
        $xoops->header();
        if (!$xoopsMailer->send()) {
            echo $xoopsMailer->getErrors();
        }
        echo "<h4>";
        printf(XoopsLocale::F_CONFIRMATION_EMAIL_SENT, $user->getVar('uname'));
        echo "</h4>";
        include __DIR__ . '/footer.php';
    }
}
