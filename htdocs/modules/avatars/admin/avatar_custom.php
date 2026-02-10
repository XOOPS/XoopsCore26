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
 * avatars module
 *
 * @copyright       XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         avatar
 * @since           2.6.0
 * @author          Mage Grégory (AKA Mage)
 * @version         $Id$
 */
include __DIR__ . '/header.php';

// Get main instance
$xoops = Xoops::getInstance();
$helper = Avatars::getInstance();

$xoops_upload_path = \XoopsBaseConfig::get('uploads-path');
$xoops_upload_url = \XoopsBaseConfig::get('uploads-url');

// Get avatar handler
$avatar_Handler = $helper->getHandlerAvatar();

// Parameters
$nb_avatars = $helper->getConfig('avatars_pager');
$mimetypes = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/x-png', 'image/png');
$upload_size = $helper->getConfig('avatars_imagefilesize');
$width = $helper->getConfig('avatars_imagewidth');
$height = $helper->getConfig('avatars_imageheight');

// Get Action type
$op = Request::getCmd('op', 'list');

// Call Header
$xoops->header('admin:avatars/avatars_admin_custom.tpl');

$admin_page = new \Xoops\Module\Admin();
$admin_page->renderNavigation('avatar_custom.php');

$info_msg = array(
    sprintf(AvatarsLocale::ALERT_INFO_MIMETYPES, implode(", ", $mimetypes)),
    sprintf(AvatarsLocale::ALERT_INFO_MAXFILE, $upload_size / 1000),
    sprintf(AvatarsLocale::ALERT_INFO_PIXELS, $width, $height)
);

switch ($op) {
    case 'list':
    default:
        // Add Scripts
        $xoops->theme()->addScript('media/xoops/xoops.js');
        // Define Stylesheet
        $xoops->theme()->addStylesheet('modules/avatars/css/admin.css');

        $admin_page->addTips(AvatarsLocale::CUSTOM_TIPS);
        $admin_page->renderTips();

        // Get start pager
        $start = Request::getInt('start', 0);
        // Filter avatars
        $criteria = new Criteria('avatar_type', 'C');
        $avatar_count = $avatar_Handler->getCount($criteria);
        $xoops->tpl()->assign('avatar_count', $avatar_count);
        // Get avatar list
        $criteria->setStart($start);
        $criteria->setLimit($nb_avatars);
        $criteria->setSort("avatar_weight");
        $criteria->setOrder("ASC");
        $avatars_arr = $avatar_Handler->getObjects($criteria, true);
        // Construct avatars array
        $avatar_list = array();
        $i = 0;
        foreach (array_keys($avatars_arr) as $i) {
            $avatar_list[$i] = $avatars_arr[$i]->getValues();
            $user = $avatar_Handler->getUser($avatars_arr[$i]);
            if (is_array($user) && isset($user[0])) {
                $avatar_list[$i]['user'] = $user[0];
            }
        }
        $xoops->tpl()->assign('avatars_list', $avatar_list);
        // Display Page Navigation
        if ($avatar_count > $nb_avatars) {
            $nav = new XoopsPageNav($avatar_count, $nb_avatars, $start, 'start', 'op=list');
            $xoops->tpl()->assign('nav_menu', $nav->renderNav(4));
        }
        break;

    // Edit
    case "edit":
        $admin_page->addItemButton(AvatarsLocale::LIST_OF_AVATARS, 'avatar_custom.php', 'application-view-detail');
        $admin_page->renderButton();
        $xoops->tpl()->assign('info_msg', $xoops->alert('info', $info_msg, XoopsLocale::INFORMATION_FOR_UPLOADS));
        // Create form
        $obj = $avatar_Handler->get(Request::getInt('avatar_id', 0));
        $form = $xoops->getModuleForm($obj, 'avatar');
        // Assign form
        $xoops->tpl()->assign('form', $form->render());
        break;

    // Save
    case "save":
        // Check security
        if (!$xoops->security()->check()) {
            $xoops->redirect('avatar_custom.php', 3, implode('<br />', $xoops->security()->getErrors()));
        }
        $uploader_avatars_img =
            new XoopsMediaUploader($xoops_upload_path . '/avatars', $mimetypes, $upload_size, $width, $height);
        // Get avatar id
        $avatar_id = Request::getInt('avatar_id', 0);
        if ($avatar_id > 0) {
            $obj = $avatar_Handler->get($avatar_id);
        } else {
            $obj = $avatar_Handler->create();
        }
        $error_msg = '';
        $obj->setVars($_POST);
        if (preg_match('/^\d+$/', $_POST["avatar_weight"]) == false) {
            $error_msg .= XoopsLocale::E_YOU_NEED_A_POSITIVE_INTEGER . '<br />';
            $obj->setVar("avatar_weight", 0);
        } else {
            $obj->setVar("avatar_weight", Request::getInt('avatar_weight', 0));
        }
        $obj->setVar('avatar_type', 'C');
        if ($uploader_avatars_img->fetchMedia('avatar_file')) {
            $uploader_avatars_img->setPrefix('savt');
            $uploader_avatars_img->fetchMedia('avatar_file');
            if (!$uploader_avatars_img->upload()) {
                $error_msg .= $uploader_avatars_img->getErrors();
                $obj->setVar('avatar_file', 'avatars/blank.gif');
            } else {
                $obj->setVar('avatar_mimetype', $uploader_avatars_img->getMediaType());
                $obj->setVar('avatar_file', 'avatars/' . $uploader_avatars_img->getSavedFileName());
            }
        } else {
            $file = Request::getString('avatar_file', 'blank.gif');
            $obj->setVar('avatar_file', 'avatars/' . $file);
        }
        if ($error_msg == '') {
            if ($avatar_Handler->insert($obj)) {
                $xoops->redirect('avatar_custom.php', 2, XoopsLocale::S_ITEM_SAVED);
            }
            $error_msg .= $obj->getHtmlErrors();
        }
        $admin_page->addItemButton(AvatarsLocale::LIST_OF_AVATARS, 'avatar_custom.php', 'application-view-detail');
        $admin_page->renderButton();
        $xoops->tpl()->assign('info_msg', $xoops->alert('info', $info_msg, XoopsLocale::INFORMATION_FOR_UPLOADS));
        $xoops->tpl()->assign('error_msg', $xoops->alert('error', $error_msg, XoopsLocale::ERRORS));
        $form = $xoops->getModuleForm($obj, 'avatar');
        $xoops->tpl()->assign('form', $form->render());
        break;

    //Delete
    case "delete":
        $avatar_id = Request::getInt('avatar_id', 0);
        $obj = $avatar_Handler->get($avatar_id);
        if (Request::getBool('ok', false, 'POST')) {
            if (!$xoops->security()->check()) {
                $xoops->redirect("avatar_custom.php", 3, implode(",", $xoops->security()->getErrors()));
            }
            if ($avatar_Handler->delete($obj)) {
                // Delete file
                $file = $obj->getVar('avatar_file');
                if ($file !== 'avatars/blank.gif') {
					$fullname = $xoops_upload_path . '/' . $file;
                    if (is_file($fullname)) {
                        chmod($fullname, 0777);
                        unlink($fullname);
                    }
                }
                // Update member profiles
                $qb = $xoops->db()->createXoopsQueryBuilder();
                $eb = $qb->expr();
                $query = $qb->updatePrefix('system_user')
                    ->set('user_avatar', 'blank.gif')
                    ->where($eb->eq('user_avatar', ':file'))
                    ->setParameter('file', $file);
                $result = $query->execute();
                $xoops->redirect("avatar_custom.php", 2, XoopsLocale::S_ITEM_SAVED);
            } else {
                echo $xoops->alert('error', $obj->getHtmlErrors());
            }
        } else {
            if ($avatar_id > 0) {
                // Define Stylesheet
                $xoops->theme()->addStylesheet('modules/system/css/admin.css');
                $msg = '<div class="spacer"><img src="' . $xoops_upload_url . '/'
                    . $obj->getVar('avatar_file', 's')
                    . '" alt="" /></div><div class="txtcenter bold">'
                    . $obj->getVar('avatar_name', 's') . '</div>'
                    . XoopsLocale::Q_ARE_YOU_SURE_YOU_WANT_TO_DELETE_THIS_ITEM;
                // Display message
                echo $xoops->confirm(
                    array('ok' => 1, 'op' => 'delete', 'avatar_id' => $avatar_id),
                    'avatar_custom.php',
                    $msg
                );
            } else {
                $xoops->redirect('avatar_custom.php', 1, XoopsLocale::E_DATABASE_NOT_UPDATED);
            }
        }
        break;

    case "update_display":
        $avatar_id = Request::getInt('avatar_id', 0);
        if ($avatar_id > 0) {
            $obj = $avatar_Handler->get($avatar_id);
            $old = $obj->getVar('avatar_display');
            $obj->setVar('avatar_display', !$old);
            if ($avatar_Handler->insert($obj)) {
                exit;
            }
            echo $obj->getHtmlErrors();
        }
        break;
}
$xoops->footer();
