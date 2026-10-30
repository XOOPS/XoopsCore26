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
require_once dirname(dirname(dirname(__DIR__))) . '/include/cp_header.php';

XoopsLoad::load('system', 'system');

$xoops = Xoops::getInstance();
$helper = Xoops\Module\Helper::getHelper('images');

// Get Action type
$op = Request::getCmd('op', 'list');
$start = Request::getInt('start', 0);
$imgcat_id = Request::getInt('imgcat_id', 0);

$redirect = basename($xoops->getEnv('SCRIPT_NAME'));
if (!$xoops->isUser() || !$xoops->isModule() || !$xoops->user->isAdmin($xoops->module->mid())) {
    exit(XoopsLocale::E_NO_ACCESS_PERMISSION);
}

$gperm_handler = $xoops->getHandlerGroupPermission();
$groups = $xoops->getUserGroups();

// check WRITE right by category before continue
// (op names must match the controllers: categories.php and images.php)
// the AJAX display toggle keeps its token valid for the next click on the same page
if ($op === 'display' && ('POST' !== Request::getMethod() || !$xoops->security()->check(false))) {
    // non-2xx so Xoops.changeStatus() does not flip the icon
    http_response_code(403);
    exit();
}
$write_cats = array();
if ($redirect === 'categories.php' && $imgcat_id > 0 && in_array($op, array('edit', 'save', 'del', 'display'), true)) {
    $write_cats[] = $imgcat_id;
}
if ($redirect === 'images.php' && in_array($op, array('add', 'edit', 'save', 'del', 'display'), true)) {
    // the requested (or target) category, and the category the image is in now
    $image_id = Request::getInt('image_id', 0);
    if ($image_id > 0 && is_object($image = $helper->getHandlerImages()->get($image_id))) {
        $write_cats[] = (int)$image->getVar('imgcat_id');
    }
    if ($imgcat_id > 0 || empty($write_cats)) {
        $write_cats[] = $imgcat_id;
    }
    unset($image_id, $image);
}
foreach (array_unique($write_cats) as $write_cat) {
    if (!$gperm_handler->checkRight('imgcat_write', $write_cat, $groups, $xoops->module->mid())) {
        $xoops->redirect($redirect, 1);
    }
}
unset($write_cats, $write_cat);

// Only website administator can delete categories or images
if (!in_array(FixedGroups::ADMIN, $groups) && $op === 'del') {
    $xoops->redirect($redirect, 1);
}

// check READ right by category before continue
if (isset($imgcat_id) && $op === 'list') {
    $imgcat_read = $gperm_handler->checkRight('imgcat_read', $imgcat_id, $groups, $xoops->module->mid());
    $imgcat_write = $gperm_handler->checkRight('imgcat_write', $imgcat_id, $groups, $xoops->module->mid());
    if (!$imgcat_read && !$imgcat_write) {
        $xoops->redirect('images.php', 1);
    }
}

// Add Script
$xoops->theme()->addScript('media/xoops/xoops.js');
$xoops->theme()->addScript('modules/system/js/admin.js');
// Add Stylesheet
$xoops->theme()->addStylesheet('modules/system/css/admin.css');
$xoops->theme()->addStylesheet('modules/images/css/admin.css');
