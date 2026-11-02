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
use Xoops\Core\Request;

/**
 * Extended User Profile
 *
 * @copyright       2000-2016 XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         profile
 * @since           2.3.0
 * @author          Taiwen Jiang <phppp@users.sourceforge.net>
 */

include __DIR__ . '/header.php';
$xoops = Xoops::getInstance();
$xoops->header();

if (!$xoops->security()->checkPost()) {
    $xoops->redirect('index.php', 3, implode('<br />', $xoops->security()->getErrors()));
}
$uid = Request::getInt('uid', 0, 'POST');
// exact "0" or "1" only: getInt() would turn "0.5" or "abc" into a valid level
$level = Request::getString('level', '', 'POST');
if (0 === $uid || !in_array($level, ['0', '1'], true)) {
    $xoops->redirect("index.php", 2, _PROFILE_AM_NOSELECTION);
}
$level = (int) $level;
$member_handler = $xoops->getHandlerMember();
$user = $member_handler->getUser($uid);
if (!$user || $user->isNew()) {
    $xoops->redirect("index.php", 2, _PROFILE_AM_USERDONEXIT);
}

if (in_array(FixedGroups::ADMIN, $user->getGroups())) {
    $xoops->redirect("index.php", 2, _PROFILE_AM_CANNOTDEACTIVATEWEBMASTERS);
}
$user->setVar('level', $level);
if ($member_handler->insertUser($user)) {
    if (1 === $level) {
        $message = _PROFILE_AM_USER_ACTIVATED;
    } else {
        $message = _PROFILE_AM_USER_DEACTIVATED;
    }
} else {
    if (1 === $level) {
        $message = _PROFILE_AM_USER_NOT_ACTIVATED;
    } else {
        $message = _PROFILE_AM_USER_NOT_DEACTIVATED;
    }
}
$xoops->redirect($xoops->url('modules/profile/userinfo.php?uid=' . $user->getVar('uid')), 3, $message);
