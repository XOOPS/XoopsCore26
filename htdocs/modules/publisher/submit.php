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
use XoopsModules\Publisher;
use XoopsModules\Publisher\Form\ItemForm;
use XoopsModules\Publisher\Helper;

/**
 * @copyright       The XUUPS Project http://sourceforge.net/projects/xuups/
 * @license         GNU GPL V2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         Publisher
 * @subpackage      Action
 * @since           1.0
 * @author          trabis <lusopoemas@gmail.com>
 * @author          The SmartFactory <www.smartfactory.ca>
 * @version         $Id$
 */
require_once __DIR__ . '/header.php';
$xoops = Xoops::getInstance();
$helper = Helper::getInstance();
$helper->loadLanguage('admin');

// Get the total number of categories
$categoriesArray = $helper->getCategoryHandler()->getCategoriesForSubmit();

if (!$categoriesArray) {
    $xoops->redirect('index.php', 1, _MD_PUBLISHER_NEED_CATEGORY_ITEM);
}

$groups = $xoops->getUserGroups();
$gpermHandler = $xoops->getHandlerGroupPermission();
$module_id = $helper->getModule()->getVar('mid');

$itemid = Request::getInt('itemid');
if (0 != $itemid) {
    // We are editing or deleting an article
    /* @var Publisher\Item $itemObj */
    $itemObj = $helper->getItemHandler()->get($itemid);
    if (!(Publisher\Utils::IsUserAdmin() || Publisher\Utils::IsUserAuthor($itemObj) || Publisher\Utils::IsUserModerator($itemObj))) {
        $xoops->redirect('index.php', 1, XoopsLocale::E_NO_ACCESS_PERMISSION);
    }
    if (!Publisher\Utils::IsUserAdmin() || !Publisher\Utils::IsUserModerator($itemObj)) {
        if (isset($_GET['op']) && 'del' === $_GET['op'] && !$helper->getConfig('perm_delete')) {
            $xoops->redirect('index.php', 1, XoopsLocale::E_NO_ACCESS_PERMISSION);
        } elseif (!$helper->getConfig('perm_edit')) {
            $xoops->redirect('index.php', 1, XoopsLocale::E_NO_ACCESS_PERMISSION);
        }
    }

    $categoryObj = $itemObj->category();
} else {
    // we are submitting a new article
    // if the user is not admin AND we don't allow user submission, exit
    if (!(Publisher\Utils::IsUserAdmin() || (1 == $helper->getConfig('perm_submit') && ($xoops->isUser() || (1 == $helper->getConfig('perm_anon_submit')))))) {
        $xoops->redirect('index.php', 1, XoopsLocale::E_NO_ACCESS_PERMISSION);
    }
    $itemObj = $helper->getItemHandler()->create();
    $categoryObj = $helper->getCategoryHandler()->create();
}

if (isset($_GET['op']) && 'clone' === $_GET['op']) {
    $formtitle = _MD_PUBLISHER_SUB_CLONE;
    $itemObj->setNew();
    $itemObj->setVar('itemid', 0);
} else {
    $formtitle = _MD_PUBLISHER_SUB_SMNAME;
}

$op = '';
if (isset($_POST['additem'])) {
    $op = 'post';
} elseif (isset($_POST['preview'])) {
    $op = 'preview';
} else {
    $op = 'add';
}

if (isset($_REQUEST['op']) && 'del' === $_REQUEST['op']) {
    $op = 'del';
}

$allowed_editors = Publisher\Utils::getEditors($gpermHandler->getItemIds('editors', $groups, $module_id));
$form_view = $gpermHandler->getItemIds('form_view', $groups, $module_id);

// This code makes sure permissions are not manipulated
$elements = [
    'summary',
    'available_page_wrap',
    'item_tag',
    'image_item',
    'item_upload_file',
    'uid',
    'datesub',
    'status',
    'item_short_url',
    'item_meta_keywords',
    'item_meta_description',
    'weight',
    'allowcomments',
    'dohtml',
    'dosmiley',
    'doxcode',
    'doimage',
    'dolinebreak',
    'notify',
    'subtitle',
    'author_alias',
];
foreach ($elements as $element) {
    if (isset($_REQUEST[$element]) && !in_array(constant('_PUBLISHER_' . mb_strtoupper($element)), $form_view)) {
        $xoops->redirect('index.php', 1, _MD_PUBLISHER_SUBMIT_ERROR);
    }
}

$item_upload_file = $_FILES['item_upload_file'] ?? '';

//stripcslashes
switch ($op) {
    case 'del':
        $confirm = $_POST['confirm'] ?? 0;

        if ($confirm) {
            if (!$xoops->security()->check()) {
                $xoops->redirect('index.php', 3, implode('<br />', $xoops->security()->getErrors()));
            }
            if (!$helper->getItemHandler()->delete($itemObj)) {
                $xoops->redirect('index.php', 2, _AM_PUBLISHER_ITEM_DELETE_ERROR . Publisher\Utils::formatErrors($itemObj->getErrors()));
            }
            $xoops->redirect('index.php', 2, sprintf(_AM_PUBLISHER_ITEMISDELETED, $itemObj->title()));
        } else {
            $xoops->header();
            echo $xoops->confirm(['op' => 'del', 'itemid' => $itemObj->getVar('itemid'), 'confirm' => 1, 'name' => $itemObj->title()], 'submit.php', _AM_PUBLISHER_DELETETHISITEM . " <br>'" . $itemObj->title() . "'. <br> <br>", _AM_PUBLISHER_DELETE);
            $xoops->footer();
        }
        break;
    case 'preview':
        // Putting the values about the ITEM in the ITEM object
        $itemObj->setVarsFromRequest();

        $xoops->header('module:publisher/publisher_submit.tpl');
        $xoTheme = $xoops->theme();
        $xoTheme->addBaseScriptAssets('@jquery');
        $xoTheme->addBaseScriptAssets('modules/publisher/js/publisher.js');
        XoopsLoad::loadFile($helper->path('footer.php'));

        $categoryObj = $helper->getCategoryHandler()->get($_POST['categoryid']);

        $item = $itemObj->toArray();
        $item['summary'] = $itemObj->body();
        $item['categoryPath'] = $categoryObj->getCategoryPath(true);
        $item['who_when'] = $itemObj->getWhoAndWhen();
        $item['comments'] = -1;
        $xoopsTpl->assign('item', $item);

        $xoopsTpl->assign('op', 'preview');
        $xoopsTpl->assign('module_home', Publisher\Utils::moduleHome());

        if ($itemid) {
            $xoopsTpl->assign('categoryPath', _MD_PUBLISHER_EDIT_ARTICLE);
            $xoopsTpl->assign('lang_intro_title', _MD_PUBLISHER_EDIT_ARTICLE);
            $xoopsTpl->assign('lang_intro_text', '');
        } else {
            $xoopsTpl->assign('categoryPath', _MD_PUBLISHER_SUB_SNEWNAME);
            $xoopsTpl->assign('lang_intro_title', sprintf(_MD_PUBLISHER_SUB_SNEWNAME, ucwords($helper->getModule()->getVar('name'))));
            $xoopsTpl->assign('lang_intro_text', $helper->getConfig('submit_intro_msg'));
        }

        /* @var Publisher\Form\ItemForm $sform */
        //        $sform = $helper->getForm($itemObj, 'item');
        $sform = new  ItemForm($itemObj);
        $sform->setTitle($formtitle);
        $sform->assign($xoopsTpl);
        $xoops->footer();

        break;
    case 'post':
        // keep the token: save errors send the user back to this form to fix and resubmit
        if (!$xoops->security()->check(false)) {
            $xoops->redirect('index.php', 3, implode('<br />', $xoops->security()->getErrors()));
        }
        // Putting the values about the ITEM in the ITEM object
        $itemObj->setVarsFromRequest();

        // Storing the item object in the database
        if (!$itemObj->store()) {
            $xoops->redirect('javascript:history.go(-1)', 2, _MD_PUBLISHER_SUBMIT_ERROR);
        }

        // attach file if any
        if ($item_upload_file && '' != $item_upload_file['name']) {
            $file_upload_result = Publisher\Utils::uploadFile(false, false, $itemObj);
            if (true !== $file_upload_result) {
                $xoops->redirect('javascript:history.go(-1)', 3, $file_upload_result);
            }
        }

        // if autoapprove_submitted. This does not apply if we are editing an article
        if (!$itemid) {
            if (_PUBLISHER_STATUS_PUBLISHED == $itemObj->getVar('status') /*$helper->getConfig('perm_autoapprove'] ==  1*/) {
                // We do not not subscribe user to notification on publish since we publish it right away

                // Send notifications
                $itemObj->sendNotifications([_PUBLISHER_NOT_ITEM_PUBLISHED]);

                $redirect_msg = _MD_PUBLISHER_ITEM_RECEIVED_AND_PUBLISHED;
                $xoops->redirect($itemObj->getItemUrl(), 2, $redirect_msg);
            } else {
                // Subscribe the user to On Published notification, if requested
                if ($itemObj->getVar('notifypub') && $xoops->isActiveModule('notifications')) {
                    $notificationHandler = Notifications::getInstance()->getHandlerNotification();
                    $notificationHandler->subscribe('item', $itemObj->getVar('itemid'), 'approved', NOTIFICATIONS_MODE_SENDONCETHENDELETE);
                }
                // Send notifications
                $itemObj->sendNotifications([_PUBLISHER_NOT_ITEM_SUBMITTED]);

                $redirect_msg = _MD_PUBLISHER_ITEM_RECEIVED_NEED_APPROVAL;
            }
        } else {
            $redirect_msg = _MD_PUBLISHER_ITEMMODIFIED;
            $xoops->redirect($itemObj->getItemUrl(), 2, $redirect_msg);
        }
        $xoops->redirect('index.php', 2, $redirect_msg);
        break;
    case 'add':
    default:
        $xoops->header('module:publisher/publisher_submit.tpl');
        $xoopsTpl = $xoops->tpl();
        $xoTheme = $xoops->theme();
        $xoTheme->addScript(PUBLISHER_URL . '/js/publisher.js');
        XoopsLoad::loadFile($helper->path('footer.php'));

        $itemObj->setVarsFromRequest();

        $xoopsTpl->assign('module_home', Publisher\Utils::moduleHome());
        if (isset($_GET['op']) && 'clone' === $_GET['op']) {
            $xoopsTpl->assign('categoryPath', _CO_PUBLISHER_CLONE);
            $xoopsTpl->assign('lang_intro_title', _CO_PUBLISHER_CLONE);
        } elseif ($itemid) {
            $xoopsTpl->assign('categoryPath', _MD_PUBLISHER_EDIT_ARTICLE);
            $xoopsTpl->assign('lang_intro_title', _MD_PUBLISHER_EDIT_ARTICLE);
            $xoopsTpl->assign('lang_intro_text', '');
        } else {
            $xoopsTpl->assign('categoryPath', _MD_PUBLISHER_SUB_SNEWNAME);
            $xoopsTpl->assign('lang_intro_title', sprintf(_MD_PUBLISHER_SUB_SNEWNAME, ucwords($helper->getModule()->getVar('name'))));
            $xoopsTpl->assign('lang_intro_text', $helper->getConfig('submit_intro_msg'));
        }
        /* @var Publisher\Form\ItemForm $sform */
        //        $sform = $helper->getForm($itemObj, 'item');
        $sform = new  ItemForm($itemObj);
        $sform->assign($xoopsTpl);
        $xoops->footer();
        break;
}
