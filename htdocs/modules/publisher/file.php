<?php

use Xmf\Request;
use XoopsModules\Publisher;
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

use XoopsModules\Publisher\Form\FileForm;
use XoopsModules\Publisher\Helper;

/**
 * @copyright       The XUUPS Project http://sourceforge.net/projects/xuups/
 * @license         GNU GPL V2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         Publisher
 * @since           1.0
 * @author          trabis <lusopoemas@gmail.com>
 * @author          The SmartFactory <www.smartfactory.ca>
 * @version         $Id$
 */
require_once __DIR__ . '/header.php';
$xoops = Xoops::getInstance();
$helper = Helper::getInstance();
$helper->loadLanguage('admin');

$op = Request::getString('op');
$fileid = Request::getInt('fileid');

if (0 == $fileid) {
    $xoops->redirect('index.php', 2, _MD_PUBLISHER_NOITEMSELECTED);
}

/* @var Publisher\File $fileObj */
$fileObj = $helper->getFileHandler()->get($fileid);

// if the selected item was not found, exit
if (!$fileObj) {
    $xoops->redirect('index.php', 1, XoopsLocale::E_NO_ACCESS_PERMISSION);
}

$itemObj = $helper->getItemHandler()->get($fileObj->getVar('itemid'));

// if the user does not have permission to modify this file, exit
if (!(Publisher\Utils::IsUserAdmin() || Publisher\Utils::IsUserModerator($itemObj) || ($xoops->isUser() && $fileObj->getVar('uid') == $xoops->user->getVar('uid')))) {
    $xoops->redirect('index.php', 1, XoopsLocale::E_NO_ACCESS_PERMISSION);
}

/* -- Available operations -- */
switch ($op) {
    case 'default':
    case 'mod':
        $xoops->header();
        // FILES UPLOAD FORM
        //        $files_form = $helper->getForm($fileObj, 'file');
        $files_form = new  FileForm($fileObj);
        $files_form->display();
        break;
    case 'modify':
        // keep the token: save errors send the user back to this form to retry
        if (!$xoops->security()->check(false)) {
            $xoops->redirect('item.php?itemid=' . $fileObj->getVar('itemid'), 3, implode('<br />', $xoops->security()->getErrors()));
        }
        $fileid = isset($_POST['fileid']) ? (int)$_POST['fileid'] : 0;

        // Creating the file object
        if (0 != $fileid) {
            $fileObj = $helper->getFileHandler()->get($fileid);
        } else {
            $xoops->redirect('index.php', 1, XoopsLocale::E_NO_ACCESS_PERMISSION);
        }

        // Putting the values in the file object
        $fileObj->setVar('name', Request::getString('name'));
        $fileObj->setVar('description', Request::getString('description'));
        $fileObj->setVar('status', Request::getInt('file_status'));

        // attach file if any
        if (isset($_FILES['item_upload_file']) && '' != $_FILES['item_upload_file']['name']) {
            $oldfile = $fileObj->getFilePath();

            // Get available mimetypes for file uploading
            $allowed_mimetypes = $helper->getMimetypeHandler()->getArrayByType();
            // TODO : display the available mimetypes to the user
            $errors = [];

            if ($helper->getConfig('perm_upload') && is_uploaded_file($_FILES['item_upload_file']['tmp_name'])) {
                if ($fileObj->checkUpload('item_upload_file', $allowed_mimetypes, $errors)) {
                    if ($fileObj->storeUpload('item_upload_file', $allowed_mimetypes, $errors)) {
                        unlink($oldfile);
                    }
                }
            }
        }

        if (!$helper->getFileHandler()->insert($fileObj)) {
            $xoops->redirect('item.php?itemid=' . $fileObj->getVar('itemid'), 3, _AM_PUBLISHER_FILE_EDITING_ERROR . Publisher\Utils::formatErrors($fileObj->getErrors()));
        }
        $xoops->redirect('item.php?itemid=' . $fileObj->getVar('itemid'), 2, _AM_PUBLISHER_FILE_EDITING_SUCCESS);
        break;
    case 'del':
        $confirm = $_POST['confirm'] ?? 0;

        if ($confirm) {
            if (!$xoops->security()->check()) {
                $xoops->redirect('item.php?itemid=' . $fileObj->getVar('itemid'), 3, implode('<br />', $xoops->security()->getErrors()));
            }
            if (!$helper->getFileHandler()->delete($fileObj)) {
                $xoops->redirect('item.php?itemid=' . $fileObj->getVar('itemid'), 2, _AM_PUBLISHER_FILE_DELETE_ERROR);
            }
            $xoops->redirect('item.php?itemid=' . $fileObj->getVar('itemid'), 2, sprintf(_AM_PUBLISHER_FILEISDELETED, $fileObj->getVar('name')));
        } else {
            // no confirm: show deletion condition
            $xoops->header();
            echo $xoops->confirm([
                                     'op' => 'del',
                                     'fileid' => $fileObj->getVar('fileid'),
                                     'confirm' => 1,
                                     'name' => $fileObj->getVar('name'),
                                 ], 'file.php', _AM_PUBLISHER_DELETETHISFILE . ' <br>' . $fileObj->getVar('name') . ' <br> <br>', _AM_PUBLISHER_DELETE);
            $xoops->footer();
        }
        break;
}
$xoops->footer();
