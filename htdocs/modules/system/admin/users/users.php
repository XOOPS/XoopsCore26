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
 * Users Manager
 *
 * @category  Xoops\Core
 * @package   users
 * @author    Kazumi Ono (AKA onokazu)
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2002-2013 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @version   Release: 2.6.0
 * @link      http://xoops.org
 * @since     1.0
 */

// Get main instance
$xoops = Xoops::getInstance();

// Check users rights
if (!$xoops->isUser() || !$xoops->isModule() || !$xoops->user->isAdmin($xoops->module->mid())) {
    exit(XoopsLocale::E_NO_ACCESS_PERMISSION);
}

//  Check is active
if (!$xoops->getModuleConfig('active_users', 'system')) {
    $xoops->redirect('admin.php', 2, XoopsLocale::E_SECTION_NOT_ACTIVE);
}

/*********************************************************/
/* Users Functions                                       */
/*********************************************************/
/**
 * synchronize number of posts credited to user
 *
 * @param int    $uid  uid of user row
 * @param string $type type of processing, 'user' for one user, 'all users' for all
 *
 * @return void
 */
function synchronize($uid, $type)
{
    $xoops = Xoops::getInstance();
    $db = $xoops->db();

    switch ($type) {
        case 'user':
            $total_posts = 0;
            /* @var $plugin SystemPluginInterface */
            $plugins = \Xoops\Module\Plugin::getPlugins();
            foreach ($plugins as $plugin) {
                if ($res = $plugin->userPosts($uid)) {
                    $total_posts += $res;
                }
            }

            $query = $db->createXoopsQueryBuilder()
                ->updatePrefix('system_user')
                ->set('posts', ':posts')
                ->where('uid = :uid')
                ->setParameter('posts', $total_posts)
                ->setParameter('uid', $uid);

            $result = $query->execute();
            //if (!$result) {
            //    $xoops->redirect("admin.php?fct=users", 1, XoopsLocale::E_USER_NOT_UPDATED);
            //}
            break;

        case 'all users':
            $sql = $db->createXoopsQueryBuilder()
                ->select('uid')
                ->fromPrefix('system_user', 'u');

            $result = $sql->execute();
            if (!$result) {
                $xoops->redirect("admin.php?fct=users", 1, XoopsLocale::E_USER_ID_NOT_FETCHED);
            }
            $rows = $result->fetchAllAssociative();
            foreach ($rows as $row) {
                synchronize($row['uid'], "user");
            }
            break;
    }
}
