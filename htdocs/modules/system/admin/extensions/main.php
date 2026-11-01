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
 * Plugins Manager
 *
 * @copyright       XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @author          Andricq Nicolas (AKA MusS)
 * @package         system
 * @version         $Id$
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
        $xoops->header('admin:system/system_extensions.tpl');
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
            SystemLocale::EXTENSIONS_ADMINISTRATION,
            $system->adminVersion('extensions', 'adminpath')
        );
        $admin_page->addBreadcrumbLink(XoopsLocale::MAIN);
        $admin_page->addTips(SystemLocale::EXTENSION_TIPS);
        $admin_page->renderBreadcrumb();
        $admin_page->renderTips();

        $system_extension = new SystemExtension();

        $extension = $system_extension->getExtensionList();

        $xoops->tpl()->assign('xoops', $xoops);
        $xoops->tpl()->assign('extension_list', $extension);
        // Call Footer
        $xoops->footer();
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
            SystemLocale::EXTENSIONS_ADMINISTRATION,
            $system->adminVersion('extensions', 'adminpath')
        );
        $admin_page->addBreadcrumbLink(XoopsLocale::A_INSTALL);
        $admin_page->renderBreadcrumb();

        $ret = array();
        $system_extension = new SystemExtension();
        $ret = $system_extension->install($module);
        if ($ret) {
            $xoops->tpl()->assign('install', 1);
            $xoops->tpl()->assign('module', $ret);
            $xoops->tpl()->assign('from_title', SystemLocale::EXTENSIONS_ADMINISTRATION);
            $xoops->tpl()->assign('from_link', $system->adminVersion('extensions', 'adminpath'));
            $xoops->tpl()->assign('title', XoopsLocale::A_INSTALL);
            $xoops->tpl()->assign('log', $system_extension->trace);
        } else {
            print_r($system_extension->error);
            //print_r($system_extension->trace);
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
            SystemLocale::EXTENSIONS_ADMINISTRATION,
            $system->adminVersion('extensions', 'adminpath')
        );
        $admin_page->addBreadcrumbLink(XoopsLocale::A_UNINSTALL);
        $admin_page->renderBreadcrumb();

        $ret = array();
        $system_extension = new SystemExtension();
        $ret = $system_extension->uninstall($module->getVar('dirname'));
        $xoops->tpl()->assign('module', $ret);
        if ($ret) {
            $xoops->tpl()->assign('from_title', SystemLocale::EXTENSIONS_ADMINISTRATION);
            $xoops->tpl()->assign('from_link', $system->adminVersion('extensions', 'adminpath'));
            $xoops->tpl()->assign('title', XoopsLocale::A_UNINSTALL);
            $xoops->tpl()->assign('log', $system_extension->trace);
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
            SystemLocale::EXTENSIONS_ADMINISTRATION,
            $system->adminVersion('extensions', 'adminpath')
        );
        $admin_page->addBreadcrumbLink(XoopsLocale::A_UPDATE);
        $admin_page->renderBreadcrumb();

        $ret = array();
        $system_extension = new SystemExtension();
        $ret = $system_extension->update($module->getVar('dirname'));
        $xoops->tpl()->assign('module', $ret);
        if ($ret) {
            $xoops->tpl()->assign('install', 1);
            $xoops->tpl()->assign('from_title', SystemLocale::EXTENSIONS_ADMINISTRATION);
            $xoops->tpl()->assign('from_link', $system->adminVersion('extensions', 'adminpath'));
            $xoops->tpl()->assign('title', XoopsLocale::A_UPDATE);
            $xoops->tpl()->assign('log', $system_extension->trace);
        }
        $folder = array(1, 2, 3);
        $system->cleanCache($folder);
        //Set active modules in cache folder
        $xoops->setActiveModules();
        // Call Footer
        $xoops->footer();
        break;
}
