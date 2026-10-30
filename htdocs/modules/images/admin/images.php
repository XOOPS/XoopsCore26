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
 * images module
 *
 * @copyright       XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @since           2.6.0
 * @author          Mage Grégory (AKA Mage)
 * @version         $Id$
 */
include __DIR__ . '/header.php';

// Call Header
$xoops->header('admin:images/images_admin_images.tpl');

$admin_page = new \Xoops\Module\Admin();
$admin_page->renderNavigation('images.php');

$mimetypes = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/x-png', 'image/png');

switch ($op) {
    case 'save':
        if (!$xoops->security()->check()) {
            $xoops->redirect('images.php?imgcat_id=' . $imgcat_id, 3, implode('<br />', $xoops->security()->getErrors()));
        }

        $msg[] = _AM_IMAGES_IMG_SAVE;

        $category = $helper->getHandlerCategories()->get($imgcat_id);
        $image_id = Request::getInt('image_id', 0);
        if (isset($image_id) && $image_id != 0) {
            $obj = $helper->getHandlerImages()->get($image_id);
            $isnew = false;
        } else {
            $obj = $helper->getHandlerImages()->create();
            $obj->setVar('image_name', 'blank.gif');
            $obj->setVar('image_mimetype', 'image/gif');
            $isnew = true;
        }

        $obj->setVar('image_nicename', Request::getString('image_nicename', ''));
        $obj->setVar('image_created', time());
        $obj->setVar('image_display', Request::getInt('image_display', 1));
        $obj->setVar('image_weight', Request::getInt('image_weight', 0));
        $obj->setVar('imgcat_id', $imgcat_id);

        // Default value
        $image_body = '';
        $error = true;
        $error_message = '';
        $xoops_upload_file = Request::getArray('xoops_upload_file', array());
        if ($_FILES[$xoops_upload_file[0]]['error'] === 0) {
            $xoops_upload_path = \XoopsBaseConfig::get('uploads-path') . '/images';
            $uploader = new XoopsMediaUploader($xoops_upload_path, $mimetypes, $category->getVar('imgcat_maxsize'), $category->getVar('imgcat_maxwidth'), $category->getVar('imgcat_maxheight'));
            if ($uploader->fetchMedia($xoops_upload_file[0])) {
                $uploader->setPrefix('img');
                if (!$uploader->upload()) {
                    $error_message .= $uploader->getErrors();
                    $obj->setVar('image_name', 'blank.gif');
                    $obj->setVar('image_mimetype', 'image/gif');
                } else {
                    $error = false;
                    $obj->setVar('image_mimetype', $uploader->getMediaType());
                    if ($category->getVar('imgcat_storetype') === 'db' && $isnew) {
                        $fp = @fopen($uploader->getSavedDestination(), 'rb');
                        $fbinary = @fread($fp, filesize($uploader->getSavedDestination()));
                        @fclose($fp);
                        $image_body = $fbinary;
                    } else {
                        $obj->setVar('image_name', 'images/' . $uploader->getSavedFileName());
                    }
                }
            }
        }
        if ($error == true) {
            $xoops->tpl()->assign('error_message', $error_message);
        } else {
            if ($image_id = $helper->getHandlerImages()->insert($obj)) {
                if ($category->getVar('imgcat_storetype') === 'db'  && $isnew) {
                    $imagebody = $helper->getHandlerImagesBody()->get($image_id);
                    if (!is_object($imagebody)) {
                        $imagebody = $helper->getHandlerImagesBody()->create();
                        $imagebody->setVar('image_id', $image_id);
                    }
                    $imagebody->setVar('image_body', $image_body);
                    if ($helper->getHandlerImagesBody()->insert($imagebody)) {
                        @unlink($uploader->getSavedDestination());
                    }
                }
                $xoops->redirect('images.php?imgcat_id=' . $imgcat_id, 2, implode('<br />', $msg));
            }
            echo $xoops->alert('error', $obj->getHtmlErrors());
        }
        $form = $helper->getForm($obj, 'image');
        $xoops->tpl()->assign('form', $form->render());
        break;

    case 'add':
        $obj =  $helper->getHandlerImages()->create();
        $obj->setVar('imgcat_id', $imgcat_id);
        $form = $helper->getForm($obj, 'image');
        $xoops->tpl()->assign('form', $form->render());
        break;

    case 'edit':
        $image_id = Request::getInt('image_id', 0);
        if ($image_id > 0) {
            $obj =  $helper->getHandlerImages()->get($image_id);
            $form = $helper->getForm($obj, 'image');
            $xoops->tpl()->assign('form', $form->render());
        }
        break;

    case 'del':
        $image_id = Request::getInt('image_id', 0);
        if ($image_id > 0) {
            $ok = Request::getInt('ok', 0);
            $obj =  $helper->getHandlerImages()->get($image_id);

            if ($ok == 1) {
                if (!$xoops->security()->check()) {
                    $xoops->redirect('images.php?imgcat_id=' . $imgcat_id, 3, implode('<br />', $xoops->security()->getErrors()));
                }
                $category = $helper->getHandlerCategories()->get($obj->getVar('imgcat_id'));

                if ($helper->getHandlerImages()->delete($obj)) {
                    if ($category->getVar('imgcat_storetype') === 'db') {
                        $helper->getHandlerImagesBody()->delete($helper->getHandlerImagesBody()->get($image_id));
                    } else {
                        unlink(\XoopsBaseConfig::get('uploads-path') . '/' . $obj->getVar('image_name'));
                    }
                    $xoops->redirect('images.php?imgcat_id=' . $imgcat_id, 2, XoopsLocale::S_DATABASE_UPDATED);
                }
            } else {
                $category = $helper->getHandlerCategories()->get($obj->getVar('imgcat_id'));
                if ($category->getVar('imgcat_storetype') === 'db') {
                    $img = \XoopsBaseConfig::get('url') . '/image.php?id=' . $image_id;
                } else {
                    $img = \XoopsBaseConfig::get('uploads-url') . '/' . $obj->getVar('image_name');
                }
                echo $xoops->confirm(
                    array('op' => 'del', 'ok' => 1, 'image_id' => $image_id, 'imgcat_id' => $obj->getVar('imgcat_id')),
                    \XoopsBaseConfig::get('url') . '/modules/images/admin/images.php',
                    sprintf(_AM_IMAGES_IMG_DELETE, $obj->getVar('image_nicename'))
                    . '<br /><br /><img src="' . $img . '" /><br />'
                );
            }
        }
        break;

    case 'display':
        $image_id = Request::getInt('image_id', 0);
        if ($image_id > 0) {
            $obj = $helper->getHandlerImages()->get($image_id);
            $old = $obj->getVar('image_display');
            $obj->setVar('image_display', !$old);
            if (!$helper->getHandlerImages()->insert($obj)) {
                $error = true;
            }
        }
        break;

    case 'list':
    default:
        // Get rights
        $imgcat_write = $gperm_handler->checkRight('imgcat_write', $imgcat_id, $groups, $xoops->module->mid());
        if ($imgcat_write) {
            $admin_page->addItemButton(_AM_IMAGES_IMG_ADD, 'images.php?op=add&imgcat_id=' . $imgcat_id, 'add');
            $admin_page->renderButton();
        }

        // Get category store type
        $category = $helper->getHandlerCategories()->get($imgcat_id);
        if (is_object($category)) {
            if ($category->getVar('imgcat_storetype') === 'db') {
                $xoops->tpl()->assign('db_store', 1);
            }
        }

        // Category Select form
        $form_category = $helper->getForm($imgcat_id, 'categoryselect');
        $xoops->tpl()->assign('form_category', $form_category->render());

        $imgcount = $helper->getHandlerImages()->countByCategory($imgcat_id);
        $images = $helper->getHandlerImages()->getByCategory($imgcat_id, $start, $helper->getConfig('images_pager'), false);
        $xoops->tpl()->assign('images', $images);

        if ($imgcount > 0 && $imgcount > $helper->getConfig('images_pager')) {
            $nav = new XoopsPageNav($imgcount, $helper->getConfig('images_pager'), $start, 'start', 'imgcat_id=' . $imgcat_id);
            $xoops->tpl()->assign('nav_menu', $nav->renderNav());
        }

        $xoops->tpl()->assign('listimg', true);
        $xoops->tpl()->assign('imgcat_id', $imgcat_id);
        $xoops->tpl()->assign('token', $xoops->security()->createToken(3600));
        break;
}
$xoops->footer();
