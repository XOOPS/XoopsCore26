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
use Doctrine\DBAL\ParameterType;

/**
 * avatars module
 *
 * @copyright 2000-2019 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @package   avatar
 * @since     2.6.0
 * @author    Mage Grégory (AKA Mage)
 */

include dirname(dirname(dirname(__DIR__))) . '/mainfile.php';

$xoops = Xoops::getInstance();
$helper = $xoops->getModuleHelper('avatars');
$helper->loadLocale();

// Get Action type
$op = Request::getCmd('op', 'list');

// If not a user, redirect
if (!$xoops->isUser()) {
    $xoops->redirect('/index.php', 3, XoopsLocale::E_NO_ACCESS_PERMISSION);
    exit();
}

// Call header
$xoops->header('module:avatars/avatars_editavatar.tpl');

// Get avatar handler
$avatar_Handler = $helper->getHandlerAvatar();

// Parameters
$mimetypes = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/x-png', 'image/png');
$upload_size = $helper->getConfig('avatars_imagefilesize');
$width = $helper->getConfig('avatars_imagewidth');
$height = $helper->getConfig('avatars_imageheight');
if ($helper->getConfig('avatars_allowupload') == 1
    && $xoops->user->getVar('posts') >= $helper->getConfig('avatars_postsrequired')
) {
    $info_msg = array(
        sprintf(AvatarsLocale::ALERT_INFO_MIMETYPES, implode(", ", $mimetypes)),
        sprintf(AvatarsLocale::ALERT_INFO_MAXFILE, $upload_size / 1000),
        sprintf(AvatarsLocale::ALERT_INFO_PIXELS, $width, $height)
    );
} else {
    $info_msg = '';
}

switch ($op) {
    case 'list':
    default:
        $xoops->tpl()->assign('uid', $xoops->user->getVar("uid"));
        $xoops->tpl()->assign('info_msg', $xoops->alert('info', $info_msg, XoopsLocale::INFORMATION_FOR_UPLOADS));
        $oldavatar = $xoops->user->getVar('user_avatar');
        if (!empty($oldavatar) && $oldavatar !== 'blank.gif') {
            $warning_msg = '<p>' . AvatarsLocale::ALERT_WARNING_OLD .'</p>';
            $xoops_upload_url = \XoopsBaseConfig::get('uploads-url');
            $warning_msg .= "<img src='" . $xoops_upload_url . '/' . $oldavatar ."' alt='&nbsp;' />";
            $xoops->tpl()->assign('warning_msg', $xoops->alert('warning', $warning_msg, XoopsLocale::WARNING));
        }

        // Create form
        $obj = $avatar_Handler->create();
        $form = $xoops->getModuleForm($obj, 'avatar_user', 'avatars');
        // Assign form
        $xoops->tpl()->assign('form', $form->render());
        break;

    case "save":
        // Check security
        if (!$xoops->security()->check()) {
            $xoops->redirect('/index.php', 3, implode('<br />', $xoops->security()->getErrors()));
        }
        $uid = Request::getInt('uid', 0);
        if (empty($uid) || $xoops->user->getVar('uid') != $uid) {
            $xoops->redirect('/index.php', 3, XoopsLocale::E_NO_ACCESS_PERMISSION);
            exit();
        }
        $uploader_avatars_img =
            new XoopsMediaUploader(\XoopsBaseConfig::get('uploads-path') . '/avatars', $mimetypes, $upload_size, $width, $height);

        $obj = $avatar_Handler->create();
        $error_msg = '';
        if ($uploader_avatars_img->fetchMedia('user_avatar')) {
            $uploader_avatars_img->setPrefix('savt');
            $uploader_avatars_img->fetchMedia('user_avatar');
            if (!$uploader_avatars_img->upload()) {
                $error_msg .= $uploader_avatars_img->getErrors();
                $obj->setVar('avatar_file', 'avatars/blank.gif');
            } else {
                $obj->setVar('avatar_name', $xoops->user->getVar('uname'));
                $obj->setVar('avatar_mimetype', $uploader_avatars_img->getMediaType());
                $obj->setVar('avatar_file', 'avatars/' . $uploader_avatars_img->getSavedFileName());
                $obj->setVar('avatar_display', 1);
                $obj->setVar('avatar_type', 'C');

                if ($error_msg == '') {
                    if ($avatar_Handler->insert($obj)) {
                        $oldavatar = $xoops->user->getVar('user_avatar');
                        $criteria = new CriteriaCompo();
                        $criteria->add(new Criteria('avatar_type', 'C'));
                        $criteria->add(new Criteria('avatar_file', $oldavatar));
                        $avatars = $avatar_Handler->getObjects($criteria);
                        if (! empty($avatars) && count($avatars) == 1 && is_object($avatars[0])) {
                            $avatar_Handler->delete($avatars[0]);
                            $xoops_upload_path = \XoopsBaseConfig::get('uploads-path');
                            $oldavatar_path = realpath($xoops_upload_path . '/' . $oldavatar);
                            if (0 === strpos($oldavatar_path, realpath($xoops_upload_path))
                                && is_file($oldavatar_path)
                            ) {
                                unlink($oldavatar_path);
                            }
                        }
                        $sql = $xoops->db()->createXoopsQueryBuilder()
                            ->updatePrefix('system_user')
                            ->set('user_avatar', ':avatar')
                            ->where('uid = :uid')
                            ->setParameter('uid', $xoops->user->getVar('uid'), ParameterType::INTEGER)
                            ->setParameter(
                                'avatar',
                                'avatars/' . $uploader_avatars_img->getSavedFileName(),
                                ParameterType::STRING
                            )
                            ->execute();
                        $avatar_Handler->addUser($obj->getVar('avatar_id'), $xoops->user->getVar('uid'));
                        $xoops->redirect($xoops->url('userinfo.php?uid=' . $uid), 2, XoopsLocale::S_ITEM_SAVED);
                    }
                    $error_msg .= $obj->getHtmlErrors();
                }
            }
        } else {
            $user_avatar = Request::getString('user_avatar', 'blank.gif');
            $oldavatar = $xoops->user->getVar('user_avatar');
            $xoops->user->setVar('user_avatar', $user_avatar);
            $member_handler = $xoops->getHandlerMember();
            if (!$member_handler->insertUser($xoops->user)) {
                echo $xoops->user->getHtmlErrors();
            }
            $criteria = new CriteriaCompo();
            $criteria->add(new Criteria('avatar_type', 'C'));
            $criteria->add(new Criteria('avatar_file', $oldavatar));
            $avatars = $avatar_Handler->getObjects($criteria);
            if (!empty($avatars) && count($avatars) == 1 && is_object($avatars[0])) {
                $avatar_Handler->delete($avatars[0]);
                $xoops_upload_path = \XoopsBaseConfig::get('uploads-path');
                $oldavatar_path = realpath($xoops_upload_path . '/' . $oldavatar);
                if (0 === strpos($oldavatar_path, realpath($xoops_upload_path)) && is_file($oldavatar_path)) {
                    unlink($oldavatar_path);
                }
            }
            if ($user_avatar !== 'blank.gif') {
                $avatars = $avatar_Handler->getObjects(new Criteria('avatar_file', $user_avatar));
                if (is_object($avatars[0])) {
                    $avatar_Handler->addUser($avatars[0]->getVar('avatar_id'), $xoops->user->getVar('uid'));
                }
            }
            $xoops->redirect($xoops->url('userinfo.php?uid=' . $uid), 2, XoopsLocale::S_ITEM_SAVED);
        }
        $xoops->tpl()->assign('uid', $xoops->user->getVar("uid"));
        $xoops->tpl()->assign('info_msg', $xoops->alert('info', $info_msg, XoopsLocale::INFORMATION_FOR_UPLOADS));
        $xoops->tpl()->assign('error_msg', $xoops->alert('error', $error_msg, XoopsLocale::ERRORS));
        $form = $xoops->getModuleForm($obj, 'avatar_user');
        $xoops->tpl()->assign('form', $form->render());
        break;
}
$xoops->footer();
