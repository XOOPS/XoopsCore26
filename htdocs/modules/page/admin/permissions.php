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
 * page module
 *
 * @copyright       2014-2019 XOOPS Project (https://xoops.org)
 * @license         GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @package         page
 * @author          Mage Grégory (AKA Mage)
 */

include __DIR__ . '/header.php';

// Get Action type
$op = Request::getString('op', 'global');

// Call header
$xoops->header('admin:page/page_admin_permissions.tpl');

$admin_page = new \Xoops\Module\Admin();
$admin_page->renderNavigation('permissions.php');

$opform = new Xoops\Form\SimpleForm('', 'opform', 'permissions.php', 'get');
$op_select = new Xoops\Form\Select('', 'op', $op);
$op_select->set('onchange', 'document.forms.opform.submit()');
$op_select->addOption('global', PageLocale::PERMISSIONS_RATE);
$op_select->addOption('view', PageLocale::PERMISSIONS_VIEW);
$opform->addElement($op_select);
$xoops->tpl()->assign('form', $opform->render());

switch ($op) {

    case 'global':
    default:
        $global_perm_array = array('1' => PageLocale::PERMISSIONS_RATE);
        $form = new Xoops\Form\GroupPermissionForm('', $module_id, 'page_global', '', 'admin/permissions.php', true);
        foreach ($global_perm_array as $perm_id => $perm_name) {
            $form->addItem($perm_id, $perm_name);
        }
        $form->display();
        break;

    case 'view':
        // Content
        $content_count = $content_Handler->countPage($start, $nb_limit);
        $content_arr = $content_Handler->getPage($start, $nb_limit);

        // Assign Template variables
        $xoops->tpl()->assign('content_count', $content_count);

        if ($content_count > 0) {
            $group_list = $xoops->getHandlerMember()->getGroupList();

            $xoops->tpl()->assign('groups', $group_list);
            // hex token, reused by every toggle on this page
            $token = $xoops->security()->createToken(3600);
            foreach (array_keys($content_arr) as $i) {
                $content_id = $content_arr[$i]->getVar('content_id');
                $perms = '';
                $groups_ids_view = $gperm_Handler->getGroupIds('page_view_item', $content_id, $module_id);
                $groups_ids_view = array_values($groups_ids_view);
                foreach (array_keys($group_list) as $j) {
                    $perms .= '<img id="loading_display' . $content_id . '_' . $j .'" src="' . $xoops->url('media/xoops/images/spinner.gif') . '" style="display:none;" alt="' . XoopsLocale::LOADING . '" />';
                    if (in_array($j, $groups_ids_view)) {
                        $perms .= "<img class=\"cursorpointer\" id=\"display" . $content_id . "_" . $j . "\" onclick=\"Xoops.changeStatus( 'permissions.php', { op: 'update_view', content_id: " . $content_id . ", group: " . $j . ", status: 'no', XOOPS_TOKEN_REQUEST: '" . $token . "' }, 'display" . $content_id . "_" . $j ."', 'permissions.php' )\" src=\"" . $xoops->url('modules/system/images/icons/default/success.png') . "\" alt=\"" . XoopsLocale::A_DISABLE . "\" title=\"" . XoopsLocale::A_DISABLE . "\" />";
                    } else {
                        $perms .= "<img class=\"cursorpointer\" id=\"display" . $content_id . "_" . $j . "\" onclick=\"Xoops.changeStatus( 'permissions.php', { op: 'update_view', content_id: " . $content_id . ", group: " . $j . ", status: 'yes', XOOPS_TOKEN_REQUEST: '" . $token . "' }, 'display" . $content_id . "_" . $j ."', 'permissions.php' )\" src=\"" . $xoops->url('modules/system/images/icons/default/cancel.png') . "\" alt=\"" . XoopsLocale::A_ENABLE . "\" title=\"" . XoopsLocale::A_ENABLE . "\" />";
                    }
                    $perms .= $group_list[$j] . '<br />';
                }
                $content['id'] = $content_id;
                $content['title'] = $content_arr[$i]->getVar('content_title');
                $content['permissions'] = $perms;
                $xoops->tpl()->appendByRef('content', $content);
                unset($content);
            }
            // Display Page Navigation
            if ($content_count > $nb_limit) {
                $nav = new XoopsPageNav($content_count, $nb_limit, $start, 'start', 'op=view');
                $xoops->tpl()->assign('nav_menu', $nav->renderNav(4));
            }
        } else {
            $xoops->tpl()->assign('error_message', PageLocale::E_NO_CONTENT);
        }
        break;

    case 'update_view':
        // keep the token valid for the next toggle on the same page
        if ('POST' !== Request::getMethod() || !$xoops->security()->check(false)) {
            // non-2xx so Xoops.changeStatus() does not flip the icon
            http_response_code(403);
            exit();
        }
        $content_id = Request::getInt('content_id', 0, 'POST');
        $group = Request::getInt('group', 0, 'POST');
        $status = Request::getString('status', '', 'POST');
        if ($content_id > 0 && $group > 0 && $status != '') {
            if ($status === 'no') {
                // deleting permissions
                $criteria = new CriteriaCompo();
                $criteria->add(new Criteria('gperm_groupid', $group));
                $criteria->add(new Criteria('gperm_itemid', $content_id));
                $criteria->add(new Criteria('gperm_modid', $module_id));
                $criteria->add(new Criteria('gperm_name', 'page_view_item'));
                $gperm_Handler->deleteAll($criteria);
            } else {
                // add permissions
                $gperm_Handler->addRight('page_view_item', $content_id, $group, $module_id);
            }
        }
        break;
}
$xoops->footer();
