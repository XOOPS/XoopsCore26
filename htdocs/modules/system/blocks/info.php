<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

use Doctrine\DBAL\ParameterType;

/**
 * Blocks functions
 *
 * @copyright   2000-2019 XOOPS Project (https://xoops.org)
 * @license     GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @author      Kazumi Ono (AKA onokazu)
 * @package     system
 */

function b_system_info_show($options)
{
    $xoops = Xoops::getInstance();
    $xoops->db();
    $myts = \Xoops\Core\Text\Sanitizer::getInstance();
    $block = array();
    if (!empty($options[3])) {
        $block['showgroups'] = true;
        $qb = $xoops->db()->createXoopsQueryBuilder();
        $eb = $qb->expr();
        $sql = $qb->select('u.uid')
            ->addSelect('u.uname')
            ->addSelect('u.email')
            ->addSelect('u.user_viewemail')
            ->addSelect('u.user_avatar')
            ->addSelect('g.name AS groupname')
            ->fromPrefix('system_usergroup', 'l')
            ->leftJoinPrefix('l', 'system_user', 'u', 'l.uid=u.uid')
            ->leftJoinPrefix('l', 'system_group', 'g', 'l.groupid=g.groupid')
            ->where($eb->eq('g.group_type', ':gtype'))
            ->orderBy('l.groupid')
            ->addOrderBy('u.uid')
            ->setParameter('gtype', 'Admin', ParameterType::STRING);
        $result = $sql->execute();
        if ($result->errorCode() < 2000) { // return 00000 is ok, 01nnn is warning
            $prev_caption = "";
            $i = 0;
            while ($userinfo = $result->fetchAssociative()) {
                $response = $xoops->service("Avatar")->getAvatarUrl($userinfo);
                $avatar = $response->getValue();
                $avatar = empty($avatar) ? \XoopsBaseConfig::get('uploads-url') . '/blank.gif' : $avatar;
                if ($prev_caption != $userinfo['groupname']) {
                    $prev_caption = $userinfo['groupname'];
                    $block['groups'][$i]['name'] = $myts->htmlSpecialChars($userinfo['groupname']);
                }
                if ($xoops->isUser()) {
                    $block['groups'][$i]['users'][] = array(
                        'id'      => $userinfo['uid'],
                        'name'    => $myts->htmlSpecialChars($userinfo['uname']),
                        'pm_link' => \XoopsBaseConfig::get('url') . "/pmlite.php?send2=1&amp;to_userid=" . $userinfo['uid'],
                        'avatar'  => $avatar
                    );
                } else {
                    if ($userinfo['user_viewemail']) {
                        $block['groups'][$i]['users'][] = array(
                            'id'       => $userinfo['uid'],
                            'name'     => $myts->htmlSpecialChars($userinfo['uname']),
                            'msg_link' => $userinfo['email'],
                            'avatar'   => $avatar
                        );
                    } else {
                        $block['groups'][$i]['users'][] = array(
                            'id'   => $userinfo['uid'],
                            'name' => $myts->htmlSpecialChars($userinfo['uname'])
                        );
                    }
                }
                ++$i;
            }
        }
    } else {
        $block['showgroups'] = false;
    }
    $block['logourl'] = \XoopsBaseConfig::get('url') . '/images/' . $options[2];
    $block['recommendlink'] = "<a href=\"javascript:openWithSelfMain('" . \XoopsBaseConfig::get('url') . "/misc.php?action=showpopups&amp;type=friend&amp;op=sendform&amp;t=" . time() . "','friend'," . $options[0] . "," . $options[1] . ")\">" . SystemLocale::RECOMMEND_US . "</a>";
    return $block;
}

function b_system_info_edit($options)
{
    $block_form = new Xoops\Form\BlockForm();
    $block_form->addElement(new Xoops\Form\Text(SystemLocale::POPUP_WINDOW_WIDTH, 'options[0]', 1, 3, $options[0]), true);
    $block_form->addElement(new Xoops\Form\Text(SystemLocale::POPUP_WINDOW_HEIGHT, 'options[1]', 1, 3, $options[1]), true);
    $block_form->addElement(new Xoops\Form\Text(sprintf(SystemLocale::F_LOGO_IMAGE_FILE_IS_LOCATED_UNDER, \XoopsBaseConfig::get('url') . "/images/"), 'options[2]', 5, 100, $options[2]), true);
    $block_form->addElement(new Xoops\Form\RadioYesNo(SystemLocale::SHOW_ADMIN_GROUPS, 'options[3]', $options[3]));
    return $block_form->render();
}
