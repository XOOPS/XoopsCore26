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

/**
 * Blocks functions
 *
 * @copyright   XOOPS Project (http://xoops.org)
 * @license     GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @author      Kazumi Ono (AKA onokazu)
 * @package     system
 * @version     $Id$
 */

function b_system_main_show()
{
    $xoops = Xoops::getInstance();
    $block = array();
    $block['lang_home'] = XoopsLocale::HOME;
    $block['lang_close'] = XoopsLocale::A_CLOSE;
    $module_handler = $xoops->getHandlerModule();
    $criteria = new CriteriaCompo(new Criteria('hasmain', 1));
    $criteria->add(new Criteria('isactive', 1));
    $criteria->add(new Criteria('weight', 0, '>'));
    $modules = $module_handler->getObjectsArray($criteria, true);
    $moduleperm_handler = $xoops->getHandlerGroupPermission();
    $groups = $xoops->getUserGroups();
    $read_allowed = $moduleperm_handler->getItemIds('module_read', $groups);
    /* @var $module XoopsModule */
    foreach ($modules as $i => $module) {
        if (in_array($i, $read_allowed)) {
            $block['modules'][$i]['name'] = $module->getVar('name');
            $block['modules'][$i]['dirname'] = $module->getVar('dirname');
            if (XoopsLoad::fileExists($xoops->path('modules/' . $module->getVar('dirname') . '/icons/logo_small.png'))) {
                $block['modules'][$i]['image'] = $xoops->url('modules/' . $module->getVar('dirname') . '/icons/logo_small.png');
            }
            if ($xoops->isModule() && ($i == $xoops->module->getVar('mid'))) {
                $block['modules'][$i]['highlight'] = true;
                $block['nothome'] = true;
            }
            if ($xoops->module && ($i == $xoops->module->getVar('mid'))) {
                $block['modules'][$i]['highlight'] = true;
                $block['nothome'] = true;
            }
             /* @var $plugin MenusPluginInterface */
            if ($xoops->isModule() && $module->getVar('dirname') == $xoops->module->getVar('dirname') && $plugin = \Xoops\Module\Plugin::getPlugin($module->getVar('dirname'), 'menus')) {
                $sublinks = $plugin->subMenus();
                foreach ($sublinks as $sublink) {
                    $block['modules'][$i]['sublinks'][] = array(
                        'name' => $sublink['name'],
                        'url'  => \XoopsBaseConfig::get('url') . '/modules/' . $module->getVar('dirname') . '/' . $sublink['url']
                    );
                }
            }

        }
    }
    return $block;
}

function b_system_main_edit($options)
{
    $xoops = Xoops::getInstance();
    $system_module = new SystemModule();
    $admin_page = new \Xoops\Module\Admin();

    // Define Stylesheet
    $xoops->theme()->addStylesheet('media/xoops/css/icons.css');
    $xoops->theme()->addStylesheet('modules/system/css/admin.css');
    // Define scripts
    $xoops->theme()->addScript('media/jquery/plugins/jquery.jeditable.js');
    $xoops->theme()->addScript('modules/system/js/module.js');
    system_addAjaxToken();

    $admin_page->addTips(SystemLocale::MENU_TIPS);
    $admin_page->renderTips();
    $list = $system_module->getModuleList();
    $xoops->tpl()->assign('modules_list', $list);
    return $xoops->tpl()->fetch('admin:system/system_modules_menu.tpl');
}
