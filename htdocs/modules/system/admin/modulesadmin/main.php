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
 * Modules Manager
 *
 * @copyright   XOOPS Project (http://xoops.org)
 * @license     GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @author      Kazumi Ono (AKA onokazu)
 * @package     system
 * @version     $Id$
 */

// Get main instance
$xoops = Xoops::getInstance();
$system = System::getInstance();

// Check users rights
if (!$xoops->isUser() || !$xoops->isModule() || !$xoops->user->isAdmin($xoops->module->mid())) {
    exit(XoopsLocale::E_NO_ACCESS_PERMISSION);
}

//if (isset($_POST)) {
//    foreach ($_POST as $k => $v) {
//        ${$k} = $v;
//    }
//}

// Get Action type
$op = Request::getString('op', 'list');
$module = Request::getString('module', '');

if (in_array($op, array('install', 'update', 'uninstall'))) {
    if (!$xoops->security()->check()) {
        $op = 'list';
    }
}
$myts = \Xoops\Core\Text\Sanitizer::getInstance();

switch ($op) {

    case 'list':
        // Call Header
        $xoops->header('admin:system/system_modules.tpl');
        // Define Stylesheet
        $xoops->theme()->addStylesheet('modules/system/css/admin.css');
        // Define scripts
        $xoops->theme()->addScript('media/jquery/plugins/jquery.jeditable.js');
        $xoops->theme()->addScript('modules/system/js/admin.js');
        $xoops->theme()->addScript('modules/system/js/module.js');
        system_addAjaxToken();
        // Define Breadcrumb and tips
        $admin_page = new \Xoops\Module\Admin();
        $admin_page->addBreadcrumbLink(SystemLocale::CONTROL_PANEL, \XoopsBaseConfig::get('url') . '/admin.php', true);
        $admin_page->addBreadcrumbLink(
            SystemLocale::MODULES_ADMINISTRATION,
            $system->adminVersion('modulesadmin', 'adminpath')
        );
        $admin_page->addBreadcrumbLink(XoopsLocale::MAIN);
        $admin_page->addTips(SystemLocale::MODULES_TIPS);
        $admin_page->renderBreadcrumb();
        $admin_page->renderTips();

        $system_module = new SystemModule();

        $list = $system_module->getModuleList();
        $install = $system_module->getInstalledModules();

        $view = Request::getString('xoopsModsView', 'large', 'cookie');
        if ($view === 'large') {
            $xoops->tpl()->assign('view_large', '');
            $xoops->tpl()->assign('view_line', 'hide');
        } else {
            $xoops->tpl()->assign('view_large', 'hide');
            $xoops->tpl()->assign('view_line', '');
        }
        $xoops->tpl()->assign('xoops', $xoops);
        $xoops->tpl()->assign('modules_list', $list);
        $xoops->tpl()->assign('modules_available', $install);
        // Call Footer
        $xoops->footer();
        break;

    case 'rename':
        $xoops->logger()->quiet();
        //$xoops->disableErrorReporting();

        $mid = Request::getInt('id', 0, 'post');
        $value = Request::getString('value', '', 'post');
        if ($mid != 0) {
            $module_handler = $xoops->getHandlerModule();
            $module = $module_handler->getById($mid);
            $module->setVar('name', $value);
            if ($module_handler->insertModule($module)) {
                echo $value;

            }
        }
        break;

    case 'order':
        $xoops->security()->requireAjaxToken();
        // Get Module Handler
        $module_handler = $xoops->getHandlerModule();
        if (isset($_POST['mod'])) {
            $i = 1;
            foreach ($_POST['mod'] as $order) {
                if ($order > 0) {
                    $module = $module_handler->getById($order);
                    //Change order only for visible modules
                    if ($module->getVar('weight') != 0) {
                        $module->setVar('weight', $i);
                        if (!$module_handler->insertModule($module)) {
                            $error = true;
                        }
                        ++$i;
                    }
                }
            }
        }
        exit;
        break;

    case 'active':
        $xoops->logger()->quiet();
        $xoops->security()->requireAjaxToken();
        //$xoops->disableErrorReporting();
        // Get module handler
        $module_handler = $xoops->getHandlerModule();
        $block_handler = $xoops->getHandlerBlock();
        $module_id = Request::getInt('mid', 0, 'post');
        if ($module_id > 0) {
            $module = $module_handler->getById($module_id);
            $old = $module->getVar('isactive');
            // Set value
            $module->setVar('isactive', !$old);
            if (!$module_handler->insertModule($module)) {
                $error = true;
            }
            $blocks = $block_handler->getByModule($module_id);
            /* @var $block XoopsBlock */
            foreach ($blocks as $block) {
                $block->setVar('isactive', !$old);
                $block_handler->insertBlock($block);
            }
            //Set active modules in cache folder
            $xoops->cache()->delete('system');
            $xoops->setActiveModules();
            echo $module->getVar('isactive');
        }
        break;

    case 'display_in_menu':
        $xoops->logger()->quiet();
        $xoops->security()->requireAjaxToken();
        //$xoops->disableErrorReporting();
        // Get module handler
        $module_handler = $xoops->getHandlerModule();
        $module_id = Request::getInt('mid', 0, 'post');
        if ($module_id > 0) {
            $module = $module_handler->getById($module_id);
            $old = $module->getVar('weight');
            // Set value
            $module->setVar('weight', !$old);
            if (!$module_handler->insertModule($module)) {
                $error = true;
            } else {
                echo !$old;
            }
        }
        break;

    case 'install':
        $module = Request::getString('dirname', '', 'post');
        // Call Header
        $xoops->header('admin:system/system_modules_logger.tpl');
        // Define Stylesheet
        $xoops->theme()->addStylesheet('modules/system/css/admin.css');
        // Define Breadcrumb and tips
        $admin_page = new \Xoops\Module\Admin();
        $admin_page->addBreadcrumbLink(SystemLocale::CONTROL_PANEL, \XoopsBaseConfig::get('url') . '/admin.php', true);
        $admin_page->addBreadcrumbLink(
            SystemLocale::MODULES_ADMINISTRATION,
            $system->adminVersion('modulesadmin', 'adminpath')
        );
        $admin_page->addBreadcrumbLink(XoopsLocale::A_INSTALL);
        $admin_page->renderBreadcrumb();

        $ret = array();
        $system_module = new SystemModule();
        $ret = $system_module->install($module);
        if ($ret) {
            $xoops->tpl()->assign('install', 1);
            $xoops->tpl()->assign('module', $ret);
            $xoops->tpl()->assign('from_title', SystemLocale::MODULES_ADMINISTRATION);
            $xoops->tpl()->assign('from_link', $system->adminVersion('modulesadmin', 'adminpath'));
            $xoops->tpl()->assign('title', XoopsLocale::A_INSTALL);
            $xoops->tpl()->assign('log', $system_module->trace);
        } else {
            print_r($system_module->error);
            //print_r($system_module->trace);
        }
        $folder = array(1, 2, 3);
        $system->cleanCache($folder);
        //Set active modules in cache folder
        $xoops->setActiveModules();
        // Call Footer
        $xoops->footer();
        break;

    case 'uninstall':
        $mid = Request::getInt('mid', 0, 'post');
        $module_handler = $xoops->getHandlerModule();
        $module = $module_handler->getById($mid);
        // Call Header
        $xoops->header('admin:system/system_modules_logger.tpl');
        // Define Stylesheet
        $xoops->theme()->addStylesheet('modules/system/css/admin.css');
        // Define Breadcrumb and tips
        $admin_page = new \Xoops\Module\Admin();
        $admin_page->addBreadcrumbLink(SystemLocale::CONTROL_PANEL, \XoopsBaseConfig::get('url') . '/admin.php', true);
        $admin_page->addBreadcrumbLink(
            SystemLocale::MODULES_ADMINISTRATION,
            $system->adminVersion('modulesadmin', 'adminpath')
        );
        $admin_page->addBreadcrumbLink(XoopsLocale::A_UNINSTALL);
        $admin_page->renderBreadcrumb();

        $ret = array();
        $system_module = new SystemModule();
        $ret = $system_module->uninstall($module->getVar('dirname'));
        $xoops->tpl()->assign('module', $ret);
        if ($ret) {
            $xoops->tpl()->assign('from_title', SystemLocale::MODULES_ADMINISTRATION);
            $xoops->tpl()->assign('from_link', $system->adminVersion('modulesadmin', 'adminpath'));
            $xoops->tpl()->assign('title', XoopsLocale::A_UNINSTALL);
            $xoops->tpl()->assign('log', $system_module->trace);
        }
        $folder = array(1, 2, 3);
        $system->cleanCache($folder);
        //Set active modules in cache folder
        $xoops->setActiveModules();
        // Call Footer
        $xoops->footer();
        break;

    case 'update':
        $mid = Request::getInt('mid', 0, 'post');
        $module_handler = $xoops->getHandlerModule();
        $block_handler = $xoops->getHandlerBlock();
        $module = $module_handler->getById($mid);
        // Call Header
        $xoops->header('admin:system/system_modules_logger.tpl');
        // Define Stylesheet
        $xoops->theme()->addStylesheet('modules/system/css/admin.css');
        // Define Breadcrumb and tips
        $admin_page = new \Xoops\Module\Admin();
        $admin_page->addBreadcrumbLink(SystemLocale::CONTROL_PANEL, \XoopsBaseConfig::get('url') . '/admin.php', true);
        $admin_page->addBreadcrumbLink(
            SystemLocale::MODULES_ADMINISTRATION,
            $system->adminVersion('modulesadmin', 'adminpath')
        );
        $admin_page->addBreadcrumbLink(XoopsLocale::A_UPDATE);
        $admin_page->renderBreadcrumb();

        $ret = array();
        $system_module = new SystemModule();
        $ret = $system_module->update($module->getVar('dirname'));
        $xoops->tpl()->assign('module', $ret);
        if ($ret) {
            $xoops->tpl()->assign('install', 1);
            $xoops->tpl()->assign('from_title', SystemLocale::MODULES_ADMINISTRATION);
            $xoops->tpl()->assign('from_link', $system->adminVersion('modulesadmin', 'adminpath'));
            $xoops->tpl()->assign('title', XoopsLocale::A_UPDATE);
            $xoops->tpl()->assign('log', $system_module->trace);
        }
        $folder = array(1, 2, 3);
        $system->cleanCache($folder);
        //Set active modules in cache folder
        $xoops->setActiveModules();
        // Call Footer
        $xoops->footer();
        break;
}
