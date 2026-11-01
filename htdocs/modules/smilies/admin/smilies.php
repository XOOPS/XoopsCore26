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
 * smilies module
 *
 * @copyright       XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         smilies
 * @since           2.6.0
 * @author          Xoops Core Development Team - Mage Grégory (AKA Mage) - Laurent JEN (aka DuDris)
 * @version         $Id$
 */

include __DIR__ . '/header.php';

// Call Header & ...
$xoops->header('admin:smilies/smilies_smilies.tpl');
$admin_page = new \Xoops\Module\Admin();
$admin_page->renderNavigation('smilies.php');
$xoops->theme()->addScript('media/xoops/xoops.js');
$xoops->theme()->addStylesheet('modules/system/css/admin.css');

// Parameters
$nb_smilies = $helper->getConfig('smilies_pager');
$mimetypes = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/x-png', 'image/png');
$upload_size = 50000;

$info_msg = array(sprintf(_AM_SMILIES_ALERT_INFO_MIMETYPES, implode(", ", $mimetypes)), sprintf(_AM_SMILIES_ALERT_INFO_MAXFILE, $upload_size));

// Get $_GET, $_POST, ...
$op = Request::getCmd('op', 'list');
$start = Request::getInt('start', 0);

switch ($op) {
    case 'list':
    default:
        $xoops->tpl()->assign('token', $xoops->security()->createToken(3600));
        $admin_page->addTips(_AM_SMILIES_TIPS);
        $admin_page->addItemButton(_AM_SMILIES_ADD, 'smilies.php?op=add', 'add');
        $admin_page->renderTips();
        $admin_page->renderButton();

        $smilies_count = $helper->getHandlerSmilies()->getCount();
        $smilies = $helper->getHandlerSmilies()->getSmilies($start, $nb_smilies, false);

        $xoops->tpl()->assign('smilies', $smilies);
        $xoops->tpl()->assign('smilies_count', $smilies_count);

        // Display Page Navigation
        if ($smilies_count > $nb_smilies) {
            $nav = new XoopsPageNav($smilies_count, $nb_smilies, $start, 'start', 'op=list');
            $xoops->tpl()->assign('nav_menu', $nav->renderNav(2));
        }
        break;

    // New smilie
    case 'add':
        $admin_page->addItemButton(_AM_SMILIES_LIST, 'smilies.php', 'application-view-detail');
        $admin_page->renderButton();
        $xoops->tpl()->assign('info_msg', $xoops->alert('info', $info_msg, _AM_SMILIES_ALERT_INFO_TITLE));
        // Create form
        $obj = $helper->getHandlerSmilies()->create();
        $form = $helper->getForm($obj, 'smilies');
        $xoops->tpl()->assign('form', $form->render());
        break;

    // Edit smilie
    case 'edit':
        $admin_page->addItemButton(_AM_SMILIES_LIST, 'smilies.php', 'application-view-detail');
        $admin_page->renderButton();
        $xoops->tpl()->assign('info_msg', $xoops->alert('info', $info_msg, _AM_SMILIES_ALERT_INFO_TITLE));
        // Create form
        $smiley_id = Request::getInt('smiley_id', 0);
        $obj = $helper->getHandlerSmilies()->get($smiley_id);
        $form = $helper->getForm($obj, 'smilies');
        $xoops->tpl()->assign('form', $form->render());
        break;

    // Save smilie
    case 'save':
        if (!$xoops->security()->check()) {
            $xoops->redirect('smilies.php', 3, implode('<br />', $xoops->security()->getErrors()));
        }

        $smiley_id = Request::getInt('smiley_id', 0);
        if (isset($smiley_id) && $smiley_id !=0) {
            $obj = $helper->getHandlerSmilies()->get($smiley_id);
        } else {
            $obj = $helper->getHandlerSmilies()->create();
        }

        $obj->setVar('smiley_code', Request::getString('smiley_code', ''));
        $obj->setVar('smiley_emotion', Request::getString('smiley_emotion', ''));
        $obj->setVar('smiley_display', Request::getBool('smiley_display', true));
        $obj->setVar('smiley_url', 'smilies/' . Request::getPath('smiley_url', ''));
        $xoops_upload_file = Request::getArray('xoops_upload_file', array());

        $error_msg = '';
        if ($_FILES[$xoops_upload_file[0]]['error'] === 0) {
            $uploader = new XoopsMediaUploader(\XoopsBaseConfig::get('uploads-path') . '/smilies', $mimetypes, $upload_size, null, null);
            if ($uploader->fetchMedia($xoops_upload_file[0])) {
                $uploader->setPrefix('smil');
                if (!$uploader->upload()) {
                    $error_msg .= $uploader->getErrors();
                    $obj->setVar('smiley_url', 'blank.gif');
                } else {
                    $obj->setVar('smiley_url', 'smilies/' . $uploader->getSavedFileName());
                }
            }
        }
        if ($error_msg == '') {
            if ($helper->getHandlerSmilies()->insert($obj)) {
                $xoops->redirect('smilies.php', 2, _AM_SMILIES_SAVE);
            }
            $error_msg .= $obj->getHtmlErrors();
        }
        $admin_page->addItemButton(_AM_SMILIES_LIST, 'smilies.php', 'application-view-detail');
        $admin_page->renderButton();
        $xoops->tpl()->assign('info_msg', $xoops->alert('info', $info_msg, _AM_SMILIES_ALERT_INFO_TITLE));
        $xoops->tpl()->assign('error_msg', $xoops->alert('error', $error_msg, _AM_SMILIES_ALERT_ERROR_TITLE));
        $form = $helper->getForm($obj, 'smilies');
        $xoops->tpl()->assign('form', $form->render());
        break;

    //Del a smilie
    case 'del':
        $smiley_id = Request::getInt('smiley_id', 0);
        $ok = Request::getInt('ok', 0);
        $obj = $helper->getHandlerSmilies()->get($smiley_id);

        if ($ok == 1) {
            if (!$xoops->security()->check()) {
                $xoops->redirect('smilies.php', 3, implode(',', $xoops->security()->getErrors()));
            }
            $path_file = \XoopsBaseConfig::get('uploads-path') . '/' . $obj->getVar('smile_url');
            if ($helper->getHandlerSmilies()->delete($obj)) {
                if (is_file($path_file)) {
                    chmod($path_file, 0777);
                    unlink($path_file);
                }
                $xoops->redirect('smilies.php', 2, _AM_SMILIES_DELETED);
            } else {
                echo $xoops->alert('error', $obj->getHtmlErrors());
            }
        } else {
            $smilies_img = ($obj->getVar('smiley_url')) ? $obj->getVar('smiley_url') : 'blank.gif';
            echo $xoops->confirm(array(
                                 'ok' => 1, 'smiley_id' => $smiley_id, 'op' => 'del'
                            ), \XoopsBaseConfig::get('url') . '/modules/smilies/admin/smilies.php', sprintf(_AM_SMILIES_SUREDEL) . '<br /><strong>' . $obj->getVar('smiley_emotion') . '</strong><br /><img src="' . \XoopsBaseConfig::get('uploads-url') . '/' . $smilies_img . '" alt="' . $obj->getVar('smiley_emotion') . '"><br />');
        }
        break;

    case 'smilies_update_display':
        $xoops->logger()->quiet();
        $xoops->security()->requireAjaxToken();
        $smiley_id = Request::getInt('smiley_id', 0);
        if ($smiley_id > 0) {
            $obj = $helper->getHandlerSmilies()->get($smiley_id);
            $old = $obj->getVar('smiley_display');
            $obj->setVar('smiley_display', !$old);
            if ($helper->getHandlerSmilies()->insert($obj)) {
                exit;
            }
            echo $obj->getHtmlErrors();
        }
        break;
}
$xoops->footer();
