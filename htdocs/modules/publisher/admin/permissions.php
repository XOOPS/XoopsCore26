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
 * @copyright       The XUUPS Project http://sourceforge.net/projects/xuups/
 * @license         GNU GPL V2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         Publisher
 * @since           1.0
 * @author          trabis <lusopoemas@gmail.com>
 * @author          The SmartFactory <www.smartfactory.ca>
 */
use Xoops\Core\Text\Sanitizer;
use Xoops\Form\GroupPermissionForm;
use XoopsModules\Publisher;

require_once __DIR__ . '/admin_header.php';

$xoops = Xoops::getInstance();
$myts = Sanitizer::getInstance();

Publisher\Utils::cpHeader();
//publisher_adminMenu(3, _AM_PUBLISHER_PERMISSIONS);

// View Categories permissions
$item_list_view = [];
$block_view = [];

$qb = $xoops->db()->createXoopsQueryBuilder();
$qb->select('categoryid', 'name')->fromPrefix('publisher_categories', '')->orderBy('name');
$result = $qb->execute();
$catArray = $result->fetchAllAssociative();
$catCount = count($catArray);

Publisher\Utils::openCollapsableBar('permissionstable_view', 'permissionsicon_view', _AM_PUBLISHER_PERMISSIONSVIEWMAN, _AM_PUBLISHER_VIEW_CATS);
if ($catCount) {
    $form_submit = new GroupPermissionForm('', $helper->getModule()->mid(), 'category_read', '', 'admin/permissions.php');
    foreach ($catArray as $myrow_view) {
        $form_submit->addItem($myrow_view['categoryid'], $myts->displayTarea($myrow_view['name']));
    }
    echo $form_submit->render();
} else {
    echo _AM_PUBLISHER_NOPERMSSET;
}
Publisher\Utils::closeCollapsableBar('permissionstable_view', 'permissionsicon_view');

// Submit Categories permissions
echo "<br>\n";
Publisher\Utils::openCollapsableBar('permissionstable_submit', 'permissionsicon_submit', _AM_PUBLISHER_PERMISSIONS_CAT_SUBMIT, _AM_PUBLISHER_PERMISSIONS_CAT_SUBMIT_DSC);
if ($catCount) {
    $form_submit = new GroupPermissionForm('', $helper->getModule()->mid(), 'item_submit', '', 'admin/permissions.php');
    foreach ($catArray as $myrow_view) {
        $form_submit->addItem($myrow_view['categoryid'], $myts->displayTarea($myrow_view['name']));
    }
    echo $form_submit->render();
} else {
    echo _AM_PUBLISHER_NOPERMSSET;
}
Publisher\Utils::closeCollapsableBar('permissionstable_submit', 'permissionsicon_submit');

// Moderators Categories permissions
echo "<br>\n";
Publisher\Utils::openCollapsableBar('permissionstable_moderation', 'permissionsicon_moderation', _AM_PUBLISHER_PERMISSIONS_CAT_MODERATOR, _AM_PUBLISHER_PERMISSIONS_CAT_MODERATOR_DSC);
if ($catCount) {
    $form_submit = new GroupPermissionForm('', $helper->getModule()->mid(), 'category_moderation', '', 'admin/permissions.php');
    foreach ($catArray as $myrow_view) {
        $form_submit->addItem($myrow_view['categoryid'], $myts->displayTarea($myrow_view['name']));
    }
    echo $form_submit->render();
} else {
    echo _AM_PUBLISHER_NOPERMSSET;
}
Publisher\Utils::closeCollapsableBar('permissionstable_moderation', 'permissionsicon_moderation');

// Form permissions
echo "<br>\n";
Publisher\Utils::openCollapsableBar('permissionstable_form', 'permissionsicon_form', _AM_PUBLISHER_PERMISSIONS_FORM, _AM_PUBLISHER_PERMISSIONS_FORM_DSC);
$form_options = [
    _PUBLISHER_SUMMARY => _AM_PUBLISHER_SUMMARY,
    //_PUBLISHER_DISPLAY_SUMMARY        => _CO_PUBLISHER_DISPLAY_SUMMARY,
    _PUBLISHER_AVAILABLE_PAGE_WRAP => _CO_PUBLISHER_AVAILABLE_PAGE_WRAP,
    _PUBLISHER_ITEM_TAG => _AM_PUBLISHER_ITEM_TAG,
    _PUBLISHER_IMAGE_ITEM => _AM_PUBLISHER_IMAGE_ITEM,
    //_PUBLISHER_IMAGE_UPLOAD           => _AM_PUBLISHER_IMAGE_UPLOAD,
    _PUBLISHER_ITEM_UPLOAD_FILE => _CO_PUBLISHER_ITEM_UPLOAD_FILE,
    _PUBLISHER_UID => _CO_PUBLISHER_UID,
    _PUBLISHER_DATESUB => _CO_PUBLISHER_DATESUB,
    _PUBLISHER_STATUS => _CO_PUBLISHER_STATUS,
    _PUBLISHER_ITEM_SHORT_URL => _CO_PUBLISHER_ITEM_SHORT_URL,
    _PUBLISHER_ITEM_META_KEYWORDS => _CO_PUBLISHER_ITEM_META_KEYWORDS,
    _PUBLISHER_ITEM_META_DESCRIPTION => _CO_PUBLISHER_ITEM_META_DESCRIPTION,
    _PUBLISHER_WEIGHT => _CO_PUBLISHER_WEIGHT,
    _PUBLISHER_ALLOWCOMMENTS => _CO_PUBLISHER_ALLOWCOMMENTS,
    //_PUBLISHER_PERMISSIONS_ITEM => _CO_PUBLISHER_PERMISSIONS_ITEM,
    // _PUBLISHER_PARTIAL_VIEW => _CO_PUBLISHER_PARTIAL_VIEW,
    _PUBLISHER_DOHTML => _CO_PUBLISHER_DOHTML,
    _PUBLISHER_DOSMILEY => _CO_PUBLISHER_DOSMILEY,
    _PUBLISHER_DOXCODE => _CO_PUBLISHER_DOXCODE,
    _PUBLISHER_DOIMAGE => _CO_PUBLISHER_DOIMAGE,
    _PUBLISHER_DOLINEBREAK => _CO_PUBLISHER_DOLINEBREAK,
    _PUBLISHER_NOTIFY => _AM_PUBLISHER_NOTIFY,
    _PUBLISHER_SUBTITLE => _CO_PUBLISHER_SUBTITLE,
    _PUBLISHER_AUTHOR_ALIAS => _CO_PUBLISHER_AUTHOR_ALIAS,
];
$form_submit = new GroupPermissionForm('', $helper->getModule()->mid(), 'form_view', '', 'admin/permissions.php');
foreach ($form_options as $key => $value) {
    $form_submit->addItem($key, $value);
}
echo $form_submit->render();
Publisher\Utils::closeCollapsableBar('permissionstable_form', 'permissionsicon_form');

// Editors permissions
echo "<br>\n";
Publisher\Utils::openCollapsableBar('permissionstable_editors', 'permissions_editors', _AM_PUBLISHER_PERMISSIONS_EDITORS, _AM_PUBLISHER_PERMISSIONS_EDITORS_DSC);
$editors = Publisher\Utils::getEditors();
$form_submit = new GroupPermissionForm('', $helper->getModule()->mid(), 'editors', '', 'admin/permissions.php');
foreach ($editors as $key => $value) {
    $form_submit->addItem($key, $value['title']);
}
echo $form_submit->render();
Publisher\Utils::closeCollapsableBar('permissionstable_editors', 'permissionsicon_editors');

// Global permissions
echo "<br>\n";
Publisher\Utils::openCollapsableBar('permissionstable_global', 'permissionsicon_global', _AM_PUBLISHER_PERMISSIONS_GLOBAL, _AM_PUBLISHER_PERMISSIONS_GLOBAL_DSC);
$form_options = [
    _PUBLISHER_SEARCH => _AM_PUBLISHER_SEARCH,
    _PUBLISHER_RATE => _AM_PUBLISHER_RATE,
];
$form_submit = new GroupPermissionForm('', $helper->getModule()->mid(), 'global', '', 'admin/permissions.php');
foreach ($form_options as $key => $value) {
    $form_submit->addItem($key, $value);
}
echo $form_submit->render();
Publisher\Utils::closeCollapsableBar('permissionstable_global', 'permissionsicon_global');

$xoops->footer();
