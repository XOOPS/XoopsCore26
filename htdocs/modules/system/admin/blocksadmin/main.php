<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

use Xoops\Core\Kernel\Criteria;
use Xoops\Core\Kernel\CriteriaCompo;
use Xoops\Core\FixedGroups;
use Xoops\Core\Kernel\Handlers\XoopsBlock;
use Xmf\Request;

/**
 * Blocks Administration
 *
 * @copyright       XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @author          Kazumi Ono (AKA onokazu)
 * @package         system
 * @subpackage      blocksadmin
 * @version         $Id$
 */

// Get main instance
$xoops = Xoops::getInstance();
$system = System::getInstance();
$system_breadcrumb = SystemBreadcrumb::getInstance();

// Check users rights
if (!$xoops->isUser() || !$xoops->isModule() || !$xoops->user->isAdmin($xoops->module->mid())) {
    exit(XoopsLocale::E_NO_ACCESS_PERMISSION);
}

// Get Action type
$op = Request::getString('op', 'list');

$filter = Request::getInt('filter', 0, 'get');
if ($filter) {
    $method = 'get';
} else {
    $method = 'default';
}

$selmod = $selgen = $selgrp = $selvis = null;
$sel = array(
    'selmod' => -2,
    'selgen' => -1,
    'selgrp' => FixedGroups::USERS,
    'selvis' => -1
);
foreach ($sel as $key => $value) {
    $default = isset($_COOKIE[$key]) ? (int)($_COOKIE[$key]) : $value;
    ${$key} = Request::getInt($key, $default, $method);
    setcookie($key, ${$key});
}

$type = Request::getString('type', '', $method);
if ($type === 'preview') {
    $op = 'preview';
}

if (isset($_GET['op'])) {
    if ($_GET['op'] === "edit" || $_GET['op'] === "delete" || $_GET['op'] === "delete_ok" || $_GET['op'] === "clone") {
        $op = $_GET['op'];
        $bid = isset($_GET['bid']) ? (int)($_GET['bid']) : 0;
    }
}

switch ($op) {

    case 'list':
        // Call Header
        $xoops->header('admin:system/system_blocks.tpl');
        // Define Stylesheet
        $xoops->theme()->addStylesheet('modules/system/css/admin.css');
        // Define scripts
        $xoops->theme()->addScript('modules/system/js/admin.js');
        $xoops->theme()->addScript('modules/system/js/blocks.js');
        system_addAjaxToken();
        // Define Breadcrumb and tips
        $admin_page = new \Xoops\Module\Admin();
        $admin_page->addBreadcrumbLink(SystemLocale::CONTROL_PANEL, \XoopsBaseConfig::get('url') . '/admin.php', true);
        $admin_page->addBreadcrumbLink(
            SystemLocale::BLOCKS_ADMINISTRATION,
            $system->adminVersion('blocksadmin', 'adminpath')
        );
        $admin_page->addBreadcrumbLink(SystemLocale::MANAGE_BLOCKS);
        $admin_page->renderBreadcrumb();
        $admin_page->addItemButton(SystemLocale::ADD_BLOCK, 'admin.php?fct=blocksadmin&amp;op=add', 'add');
        $admin_page->renderButton();
        $admin_page->addTips(sprintf(
            SystemLocale::BLOCKS_TIPS,
            system_AdminIcons('block.png'),
            system_AdminIcons('success.png'),
            system_AdminIcons('cancel.png'),
            SystemLocale::DRAG_OR_SORT_BLOCK,
            SystemLocale::DISPLAY_BLOCK,
            SystemLocale::HIDE_BLOCK
        ));
        $admin_page->renderTips();
        // Initialize module handler
        $module_handler = $xoops->getHandlerModule();
        $modules = $module_handler->getObjects(null, true);
        $criteria = new CriteriaCompo(new Criteria('hasmain', 1));

        $criteria->add(new Criteria('isactive', 1));
        // Modules for blocks to be visible in
        $display_list = $module_handler->getNameList($criteria);
        unset($criteria);
        // Initialize blocks handler
        $block_handler = $xoops->getHandlerBlock();
        $modules = $xoops->getHandlerModule()->getObjects(null, true);

        $filterform = new Xoops\Form\ThemeForm('', 'filterform', 'admin.php', 'get');
        $filterform->addElement(new Xoops\Form\Hidden('fct', 'blocksadmin'));
        $filterform->addElement(new Xoops\Form\Hidden('op', 'list'));
        $filterform->addElement(new Xoops\Form\Hidden('filter', 1));
        $sel_gen = new Xoops\Form\Select(XoopsLocale::MODULES, 'selgen', $selgen);
        $sel_gen->setExtra("onchange='submit()'");
        $sel_gen->addOption(-1, XoopsLocale::ALL_TYPES);
        $sel_gen->addOption(0, SystemLocale::CUSTOM_BLOCK);
        /* @var $list XoopsModule */
        foreach ($modules as $list) {
            $sel_gen->addOption($list->getVar('mid'), $list->getVar('name'));
        }
        $filterform->addElement($sel_gen);

        $sel_mod = new Xoops\Form\Select(XoopsLocale::PAGE, 'selmod', $selmod);
        $sel_mod->setExtra("onchange='submit()'");
        ksort($display_list);
        $display_list_spec[0] = XoopsLocale::ALL_PAGES;
        $display_list_spec[-1] = XoopsLocale::TOP_PAGE;
        $display_list_spec[-2] = XoopsLocale::ALL_TYPES;
        $display_list = $display_list_spec + $display_list;
        foreach ($display_list as $k => $v) {
            $sel_mod->addOption($k, $v);
        }
        $filterform->addElement($sel_mod);

        // For selection of group access
        $sel_grp = new Xoops\Form\Select(XoopsLocale::GROUPS, 'selgrp', $selgrp);
        $sel_grp->setExtra("onchange='submit()'");
        $member_handler = $xoops->getHandlerMember();
        $group_list = $member_handler->getGroupList();
        $sel_grp->addOption(-1, XoopsLocale::ALL_TYPES);
        $sel_grp->addOption(0, XoopsLocale::UNASSIGNED);
        foreach ($group_list as $k => $v) {
            $sel_grp->addOption($k, $v);
        }
        $filterform->addElement($sel_grp);

        $sel_vis = new Xoops\Form\Select(XoopsLocale::VISIBLE, 'selvis', $selvis);
        $sel_vis->setExtra("onchange='submit()'");
        $sel_vis->addOption(-1, XoopsLocale::ALL_TYPES);
        $sel_vis->addOption(0, XoopsLocale::NO);
        $sel_vis->addOption(1, XoopsLocale::YES);

        $filterform->addElement($sel_vis);

        $filterform->assign($xoops->tpl());

        /* Get blocks */
        $selvis = ($selvis == -1) ? null : $selvis;
        $selmod = ($selmod == -2) ? null : $selmod;
        $order_block = (isset($selvis) ? "" : "b.visible DESC, ") . "b.side,b.weight,b.bid";

        if ($selgrp == 0) {
            // get blocks that are not assigned to any groups
            $blocks_arr = $block_handler->getNonGroupedBlocks($selmod, $toponlyblock = false, $selvis, $order_block);
        } else {
            $selgrp = ($selgrp == -1) ? null : $selgrp;
            $blocks_arr = $block_handler->
                getAllByGroupModule($selgrp, $selmod, $toponlyblock = false, $selvis, $order_block);
        }

        if ($selgen >= 0) {
            foreach (array_keys($blocks_arr) as $bid) {
                if ($blocks_arr[$bid]->getVar("mid") != $selgen) {
                    unset($blocks_arr[$bid]);
                }
            }
        }

        $arr = array();
        foreach (array_keys($blocks_arr) as $i) {
            $arr[$i] = $blocks_arr[$i]->getValues();
            $xoops->tpl()->appendByRef('blocks', $arr[$i]);
        }
        // Call Footer
        $xoops->footer();
        break;

    case 'add':
        // Call Header
        $xoops->header('admin:system/system_blocks.tpl');
        // Define Stylesheet
        $xoops->theme()->addStylesheet('modules/system/css/admin.css');
        // Define scripts
        $xoops->theme()->addScript('media/jquery/plugins/jquery.form.js');
        $xoops->theme()->addScript('modules/system/js/admin.js');
        $xoops->theme()->addScript('modules/system/js/blocks.js');
        system_addAjaxToken();
        // Define Breadcrumb and tips
        $admin_page = new \Xoops\Module\Admin();
        $admin_page->addBreadcrumbLink(SystemLocale::CONTROL_PANEL, \XoopsBaseConfig::get('url') . '/admin.php', true);
        $admin_page->addBreadcrumbLink(
            SystemLocale::BLOCKS_ADMINISTRATION,
            $system->adminVersion('blocksadmin', 'adminpath')
        );
        $admin_page->addBreadcrumbLink(SystemLocale::ADD_BLOCK);
        $admin_page->renderBreadcrumb();
        // Initialize blocks handler
        $block_handler = $xoops->getHandlerBlock();
        $block = $block_handler->create();
        $form = $xoops->getModuleForm($block, 'block');
        $form->getForm();
        $form->display();
        // Call Footer
        $xoops->footer();
        break;

    case 'display':
        $xoops->security()->requireAjaxToken();
        // Initialize blocks handler
        $block_handler = $xoops->getHandlerBlock();
        // Get variable
        $block_id = Request::getInt('bid', 0, 'post');
        $visible = Request::getInt('visible', 0, 'post');
        if ($block_id > 0) {
            $block = $block_handler->get($block_id);
            $block->setVar('visible', $visible);
            if (!$block_handler->insertBlock($block)) {
                $error = true;
            }
        }
        break;

    case 'drag':
        $xoops->security()->requireAjaxToken();
        // Initialize blocks handler
        $block_handler = $xoops->getHandlerBlock();
        // Get variable
        $block_id = Request::getInt('bid', 0, 'post');
        $side = Request::getInt('side', 0, 'post');
        if ($block_id > 0) {
            $block = $block_handler->get($block_id);
            $block->setVar('side', $side);
            if (!$block_handler->insertBlock($block)) {
                $error = true;
            }
        }
        break;

    case 'order':
        $xoops->security()->requireAjaxToken();
        // Initialize blocks handler
        $block_handler = $xoops->getHandlerBlock();
        if (isset($_POST['blk'])) {
            $i = 0;
            foreach ($_POST['blk'] as $order) {
                if ($order > 0) {
                    $block = $block_handler->get($order);
                    $block->setVar('weight', $i);
                    if (!$block_handler->insertBlock($block)) {
                        $error = true;
                    }
                    ++$i;
                }
            }
        }
        exit;
        break;

    case 'preview':
        if (!$xoops->security()->check()) {
            $xoops->redirect('admin.php?fct=blocksadmin', 3, implode('<br />', $xoops->security()->getErrors()));
            exit();
        }
        // Initialize blocks handler
        $block_handler = $xoops->getHandlerBlock();
        /* @var $block XoopsBlock */
        $block = $block_handler->create();
        $block->setVars($_POST);
        $content = isset($_POST['content_block']) ? $_POST['content_block'] : '';
        $block->setVar('content', $content);
        $myts = \Xoops\Core\Text\Sanitizer::getInstance();
        echo '<div id="xo-preview-dialog" title="' . $block->getVar('title', 's')
            . '">' . $block->getContent('s', $block->getVar('c_type')) . '</div>';
        break;

    case 'save':
        if (!$xoops->security()->check()) {
            $xoops->redirect('admin.php?fct=blocksadmin', 3, implode('<br />', $xoops->security()->getErrors()));
            exit();
        }
        // Initialize blocks handler
        $block_handler = $xoops->getHandlerBlock();
        // Get avatar id
        $block_id = Request::getInt('bid', 0, 'post');
        if ($block_id > 0) {
            $block = $block_handler->get($block_id);
        } else {
            $block = $block_handler->create();
        }
        $block_type = Request::getString('block_type', '', 'post');
        $block->setVar('block_type', $block_type);

        if (!$block->isCustom()) {
            $block->setVars($_POST);
            $type = $block->getVar('block_type');
            $name = $block->getVar('name');
            // Save block options
            $options = $_POST['options'];
            if (isset($options)) {
                $options_count = count($options);
                if ($options_count > 0) {
                    //Convert array values to comma-separated
                    for ($i = 0; $i < $options_count; ++$i) {
                        if (is_array($options[$i])) {
                            $options[$i] = implode(',', $options[$i]);
                        }
                    }
                    $options = implode('|', $options);
                    $block->setVar('options', $options);
                }
            }
        } else {
            $block->setVars($_POST);
            switch ($block->getVar('c_type')) {
                case 'H':
                    $name = SystemLocale::CUSTOM_BLOCK_HTML;
                    break;
                case 'P':
                    $name = SystemLocale::CUSTOM_BLOCK_PHP;
                    break;
                case 'S':
                    $name = SystemLocale::CUSTOM_BLOCK_AUTO_FORMAT_SMILIES;
                    break;
                default:
                    $name = SystemLocale::CUSTOM_BLOCK_AUTO_FORMAT;
                    break;
            }
        }
        $block->setVar('name', $name);
        $block->setVar('isactive', 1);

        $content = isset($_POST['content_block']) ? $_POST['content_block'] : '';
        $block->setVar('content', $content);

        if (!$newid = $block_handler->insertBlock($block)) {
            $xoops->header();
            echo $xoops->alert('error', $block->getHtmlErrors());
            $xoops->footer();
            exit();
        }
        if ($newid != 0) {
            $blockmodulelink_handler = $xoops->getHandlerBlockModuleLink();
            // Delete old link
            $criteria = new CriteriaCompo(new Criteria('block_id', $newid));
            $blockmodulelink_handler->deleteAll($criteria);
            // Assign link
            $modules = $_POST['modules'];
            foreach ($modules as $mid) {
                $blockmodulelink = $blockmodulelink_handler->create();
                $blockmodulelink->setVar('block_id', $newid);
                $blockmodulelink->setVar('module_id', $mid);
                if (!$blockmodulelink_handler->insert($blockmodulelink)) {
                    $xoops->header();
                    echo $xoops->alert('error', $blockmodulelink->getHtmlErrors());
                    $xoops->footer();
                    exit();
                }
            }
        }
        $groupperm_handler = $xoops->getHandlerGroupPermission();
        $groups = $_POST['groups'];
        $groups_with_access = $groupperm_handler->getGroupIds("block_read", $newid);
        $removed_groups = array_diff($groups_with_access, $groups);
        if (count($removed_groups) > 0) {
            foreach ($removed_groups as $groupid) {
                $criteria = new CriteriaCompo(new Criteria('gperm_name', 'block_read'));
                $criteria->add(new Criteria('gperm_groupid', $groupid));
                $criteria->add(new Criteria('gperm_itemid', $newid));
                $criteria->add(new Criteria('gperm_modid', 1));
                $perm = $groupperm_handler->getObjects($criteria);
                if (isset($perm[0]) && is_object($perm[0])) {
                    $groupperm_handler->delete($perm[0]);
                }
            }
        }
        $new_groups = array_diff($groups, $groups_with_access);
        if (count($new_groups) > 0) {
            foreach ($new_groups as $groupid) {
                $groupperm_handler->addRight("block_read", $newid, $groupid);
            }
        }
        $xoops->redirect('admin.php?fct=blocksadmin', 1, XoopsLocale::S_DATABASE_UPDATED);
        break;

    case 'edit':
        // Initialize blocks handler
        $block_handler = $xoops->getHandlerBlock();
        // Get avatar id
        $block_id = Request::getInt('bid', 0);
        if ($block_id > 0) {
            // Call Header
            $xoops->header('admin:system/system_blocks.tpl');
            // Define Stylesheet
            $xoops->theme()->addStylesheet('modules/system/css/admin.css');
            $xoops->theme()->addScript('media/jquery/plugins/jquery.form.js');
            $xoops->theme()->addScript('modules/system/js/admin.js');
            // Define Breadcrumb and tips
            $admin_page = new \Xoops\Module\Admin();
            $admin_page->addBreadcrumbLink(SystemLocale::CONTROL_PANEL, \XoopsBaseConfig::get('url') . '/admin.php', true);
            $admin_page->addBreadcrumbLink(
                SystemLocale::BLOCKS_ADMINISTRATION,
                $system->adminVersion('blocksadmin', 'adminpath')
            );
            $admin_page->addBreadcrumbLink(SystemLocale::EDIT_BLOCK);
            $admin_page->renderBreadcrumb();
            $block = $block_handler->get($block_id);
            /* @var $form SystemBlockForm */
            $form = $xoops->getModuleForm($block, 'block');
            $form->getForm();
            $form->display();
            // Call Footer
            $xoops->footer();
        } else {
            $xoops->redirect('admin.php?fct=blocksadmin', 1, XoopsLocale::E_DATABASE_NOT_UPDATED);
        }
        break;

    case 'delete':
        // Call Header
        $xoops->header('admin:system/system_blocks.tpl');
        // Define Stylesheet
        $xoops->theme()->addStylesheet('modules/system/css/admin.css');
        // Define Breadcrumb and tips
        $admin_page = new \Xoops\Module\Admin();
        $admin_page->addBreadcrumbLink(SystemLocale::CONTROL_PANEL, \XoopsBaseConfig::get('url') . '/admin.php', true);
        $admin_page->addBreadcrumbLink(
            SystemLocale::BLOCKS_ADMINISTRATION,
            $system->adminVersion('blocksadmin', 'adminpath')
        );
        $admin_page->addBreadcrumbLink(SystemLocale::DELETE_BLOCK);
        $admin_page->renderBreadcrumb();
        // Initialize blocks handler
        $block_handler = $xoops->getHandlerBlock();
        // Get avatar id
        $block_id = Request::getInt('bid', 0);
        if ($block_id > 0) {
            $block = $block_handler->get($block_id);
            if ($block->getVar('block_type') === XoopsBlock::BLOCK_TYPE_SYSTEM) {
                $xoops->redirect('admin.php?fct=blocksadmin', 4, SystemLocale::E_SYSTEM_BLOCKS_CANNOT_BE_DELETED);
                exit();
            } elseif ($block->getVar('block_type') === XoopsBlock::BLOCK_TYPE_MODULE) {
                // Fix for duplicated blocks created in 2.0.9 module update
                // A module block can be deleted if there is more than 1 that
                // has the same func_num/show_func which is mostly likely
                // be the one that was duplicated in 2.0.9
                if (1 >= $count = $block_handler->countSimilarBlocks(
                    $block->getVar('mid'),
                    $block->getVar('func_num'),
                    $block->getVar('show_func')
                )) {
                    $xoops->redirect('admin.php?fct=blocksadmin', 4, SystemLocale::E_THIS_BLOCK_CANNOT_BE_DELETED);
                    exit();
                }
            }
            // Call Header
            $xoops->header('admin:system/system_header.tpl');
            // Display Question
            echo $xoops->confirm(array(
                'op'  => 'delete_ok',
                'fct' => 'blocksadmin',
                'bid' => $block->getVar('bid')
            ), 'admin.php', sprintf(SystemLocale::QF_ARE_YOU_SURE_TO_DELETE_THIS_BLOCK, $block->getVar('title')));
            // Call Footer
            $xoops->footer();
        }
        break;

    case 'delete_ok':
        if (!$xoops->security()->check()) {
            $xoops->redirect('admin.php?fct=blocksadmin', 3, implode('<br />', $xoops->security()->getErrors()));
            exit();
        }
        // Initialize blocks handler
        $block_handler = $xoops->getHandlerBlock();
        // Get avatar id
        $block_id = Request::getInt('bid', 0, 'post');
        if ($block_id > 0) {
            $block = $block_handler->get($block_id);
            if ($block_handler->deleteBlock($block)) {
                // Delete Group link
                $blockmodulelink_handler = $xoops->getHandlerBlockModuleLink();
                $blockmodulelink =
                    $blockmodulelink_handler->getObjects(new CriteriaCompo(new Criteria('block_id', $block_id)));
                foreach ($blockmodulelink as $link) {
                    $blockmodulelink_handler->delete($link, true);
                }
                // Delete Group permission
                $groupperm_handler = $xoops->getHandlerGroupPermission();
                $criteria = new CriteriaCompo(new Criteria('gperm_name', 'block_read'));
                $criteria->add(new Criteria('gperm_itemid', $block_id));
                $groupperm = $groupperm_handler->getObjects($criteria);
                /* @var $perm XoopsGroupPerm */
                foreach ($groupperm as $perm) {
                    $groupperm_handler->delete($perm, true);
                }
                // Delete template
                if ($block->getVar('template') != '') {
                    $tplfile_handler = $xoops->getHandlerTplFile();
                    $btemplate = $tplfile_handler->find($xoops->getConfig('template_set'), 'block', $block_id);
                    if (count($btemplate) > 0) {
                        $tplfile_handler->deleteTpl($btemplate[0]);
                    }
                }
                $xoops->redirect('admin.php?fct=blocksadmin', 1, XoopsLocale::S_DATABASE_UPDATED);
            }
        } else {
            $xoops->redirect('admin.php?fct=blocksadmin', 1, XoopsLocale::E_DATABASE_NOT_UPDATED);
        }
        break;

    case 'clone':
        // Initialize blocks handler
        $block_handler = $xoops->getHandlerBlock();
        // Get avatar id
        $block_id = Request::getInt('bid', 0);
        if ($block_id > 0) {
            // Call Header
            $xoops->header('admin:system/system_blocks.tpl');
            // Define Stylesheet
            $xoops->theme()->addStylesheet('modules/system/css/admin.css');
            // Define Breadcrumb and tips
            $system_breadcrumb = new \Xoops\Module\Admin();
            $system_breadcrumb->addBreadcrumbLink(
                SystemLocale::BLOCKS_ADMINISTRATION,
                system_adminVersion('blocksadmin', 'adminpath')
            );
            $system_breadcrumb->addBreadcrumbLink(SystemLocale::CLONE_BLOCK);
            $system_breadcrumb->renderBreadcrumb();

            $block = $block_handler->get($block_id);
            /* @var $form SystemBlockForm */
            $form = $xoops->getModuleForm($block, 'block');
            $form->getForm('clone');
            $form->display();
            // Call Footer
            $xoops->footer();
        } else {
            $xoops->redirect('admin.php?fct=blocksadmin', 1, XoopsLocale::E_DATABASE_NOT_UPDATED);
        }
        break;
}
