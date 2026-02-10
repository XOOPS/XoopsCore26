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
 * Users Manager
 *
 * @copyright   2000-2019 XOOPS Project (https://xoops.org)
 * @license     GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @author      Maxime Cointin (AKA Kraven30)
 * @package     system
 * @version     $Id$
 */

include dirname(dirname(__DIR__)) . '/header.php';

$xoops = Xoops::getInstance();

if (!$xoops->isUser() || !$xoops->isModule() || !$xoops->user->isAdmin($xoops->module->mid())) {
    exit(XoopsLocale::E_NO_ACCESS_PERMISSION);
}

$xoops->logger()->quiet();

Request::getString('op', 'default');

switch ($op) {
    // Display post
    case 'display_post':
        include_once $xoops->path('modules/system/include/functions.php');

        $uid = Request::getInt('uid', 0);
        $total_posts = 0;

        /* @var $plugin SystemPluginInterface */
        $plugins = \Xoops\Module\Plugin::getPlugins();
        foreach ($plugins as $plugin) {
            if ($res = $plugin->userPosts($uid)) {
                $total_posts += $res;
            }
        }

        $qb = $xoops->db()->createXoopsQueryBuilder();
        $eb = $qb->expr();
        $sql = $qb->updatePrefix('system_user')
            ->set('posts', ':posts')
            ->where('uid = :uid')
            ->setParameter('posts', $total_posts)
            ->setParameter('uid', $uid);
        $row_count = $sql->execute();
        echo $row_count;
        break;
    default:
        break;
}
