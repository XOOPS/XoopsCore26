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
use Xoops\Core\FixedGroups;
use Xoops\Core\Kernel\Criteria;
use Xoops\Core\Kernel\CriteriaCompo;


/**
 * Extended User Profile
 *
 * @copyright       2000-2019 XOOPS Project (https://xoops.org)
 * @license         GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @package         profile
 * @since           2.3.0
 * @author          Jan Pedersen
 * @author          Taiwen Jiang <phppp@users.sourceforge.net>
 */
include __DIR__ . '/header.php';
// Get main instance
$xoops = Xoops::getInstance();

//there is no way to override current tabs when using system menu
//this dirty hack will have to do it
$_SERVER['REQUEST_URI'] = "admin/permissions.php";

// Get Action type
$op = Request::getString('op', 'visibility');

// Call header
$xoops->header();

$admin_page = new \Xoops\Module\Admin();
$admin_page->displayNavigation('permissions.php');

$visibility_handler = \Xoops::getModuleHelper('profile')->getHandler('visibility');
$field_handler = \Xoops::getModuleHelper('profile')->getHandler('field');
$fields = $field_handler->getList();

$fieldId = Request::getInt('field_id', 0);
$userGroup = Request::getInt('ug', 0);
$profileGroup = Request::getInt('pg', 0);
if (isset($_POST['submit'])) {
    if (!$xoops->security()->check()) {
        $xoops->redirect('visibility.php', 3, implode(',', $xoops->security()->getErrors()));
    }
    $visibility = $visibility_handler->create();
    $visibility->setVar('field_id', $fieldId);
    $visibility->setVar('user_group', $userGroup);
    $visibility->setVar('profile_group', $profileGroup);
    $visibility_handler->insert($visibility, true);
    $xoops->redirect("visibility.php", 2, sprintf(_PROFILE_AM_SAVEDSUCCESS, _PROFILE_AM_PROF_VISIBLE));
}
if ($op === "del") {
    if (1 === Request::getInt('ok', 0, 'POST')) {
        if (!$xoops->security()->check()) {
            $xoops->redirect('visibility.php', 3, implode(',', $xoops->security()->getErrors()));
        }
        $criteria = new CriteriaCompo(new Criteria('field_id', $fieldId));
        $criteria->add(new Criteria('user_group', $userGroup));
        $criteria->add(new Criteria('profile_group', $profileGroup));
        $visibility_handler->deleteAll($criteria, true);
        $xoops->redirect("visibility.php", 2, sprintf(_PROFILE_AM_DELETEDSUCCESS, _PROFILE_AM_PROF_VISIBLE));
    }
    echo $xoops->confirm(
        array('ok' => 1, 'op' => 'del', 'field_id' => $fieldId, 'ug' => $userGroup, 'pg' => $profileGroup),
        'visibility.php',
        XoopsLocale::Q_ARE_YOU_SURE_YOU_WANT_TO_DELETE_THIS_ITEM
    );
    $xoops->footer();
}

$opform = new Xoops\Form\SimpleForm('', 'opform', 'permissions.php', "get");
$op_select = new Xoops\Form\Select("", 'op', $op);
$op_select->setExtra('onchange="document.forms.opform.submit()"');
$op_select->addOption('visibility', _PROFILE_AM_PROF_VISIBLE);
$op_select->addOption('edit', _PROFILE_AM_PROF_EDITABLE);
$op_select->addOption('search', _PROFILE_AM_PROF_SEARCH);
$op_select->addOption('access', _PROFILE_AM_PROF_ACCESS);
$opform->addElement($op_select);
$opform->display();

$criteria = new CriteriaCompo();
//$criteria->setGroupBy('field_id, user_group, profile_group');
$criteria->setSort('field_id, user_group, profile_group');
$criteria->setOrder('DESC');

$visibilities = $visibility_handler->getAllByFieldId($criteria);

$member_handler = $xoops->getHandlerMember();
$groups = $member_handler->getGroupList();
$groups[0] = _PROFILE_AM_FIELDVISIBLETOALL;
asort($groups);

$xoops->tpl()->assign('fields', $fields);
$xoops->tpl()->assign('visibilities', $visibilities);
$xoops->tpl()->assign('groups', $groups);

$add_form = new Xoops\Form\SimpleForm('', 'addform', 'visibility.php', 'post', true);

$sel_field = new Xoops\Form\Select(_PROFILE_AM_FIELDVISIBLE, 'field_id');
$sel_field->addOptionArray($fields);
$add_form->addElement($sel_field);

$sel_ug = new Xoops\Form\Select(_PROFILE_AM_FIELDVISIBLEFOR, 'ug');
$sel_ug->addOptionArray($groups);
$add_form->addElement($sel_ug);

unset($groups[FixedGroups::ANONYMOUS]);
$sel_pg = new Xoops\Form\Select(_PROFILE_AM_FIELDVISIBLEON, 'pg');
$sel_pg->addOptionArray($groups);
$add_form->addElement($sel_pg);

$add_form->addElement(new Xoops\Form\Button('', 'submit', XoopsLocale::A_ADD, 'submit'));
$add_form->assign($xoops->tpl());

$xoops->tpl()->display("admin:profile/visibility.tpl");

$xoops->footer();
