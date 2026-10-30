<?php
/**
 * XOOPS control panel header
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         include
 * @since           2.0.0
 * @version         $Id$
 */

/**
 * module files can include this file for admin authorization
 * the file that will include this file must be located under
 * xoops_url/modules/module_directory_name/admin_directory_name/
 */

require_once dirname(__DIR__) . '/mainfile.php';

$xoops = Xoops::getInstance();
$xoops_url = \XoopsBaseConfig::get('url');
include_once $xoops->path('include/cp_functions.php');

$moduleperm_handler = $xoops->getHandlerGroupPermission();
if ($xoops->isUser()) {
    // take the module from the script being run, not from the request URI
    $dirname = '';
    $modulesPath = rtrim($xoops->normalizePath($xoops->path('modules')), '/') . '/';
    $scriptPath = $xoops->normalizePath($_SERVER['SCRIPT_FILENAME']);
    if (0 === strpos($scriptPath, $modulesPath)) {
        $parts = explode('/', substr($scriptPath, strlen($modulesPath)));
        if (count($parts) > 1 && !in_array('..', $parts, true)) {
            $dirname = $parts[0];
        }
    }
    // the server may report the resolved path: match it against each resolved module folder,
    // so symlinked modules (or a symlinked modules folder) still work
    $scriptPath = realpath($_SERVER['SCRIPT_FILENAME']);
    if ('' === $dirname && false !== $scriptPath) {
        $scriptPath = $xoops->normalizePath($scriptPath);
        foreach ($xoops->getActiveModules() as $activeDirname) {
            $modulePath = realpath($xoops->path('modules/' . $activeDirname));
            if (false !== $modulePath
                && 0 === strpos($scriptPath, rtrim($xoops->normalizePath($modulePath), '/') . '/')
            ) {
                $dirname = $activeDirname;
                break;
            }
        }
    }
    if (!$xoops->isActiveModule($dirname)) {
        $xoops->redirect($xoops_url, 1, XoopsLocale::E_NO_ACCESS_PERMISSION);
    }
    $xoops->module = $xoops->getModuleByDirname($dirname);
    unset($dirname, $modulesPath, $scriptPath, $parts, $activeDirname, $modulePath);
    if (!$moduleperm_handler->checkRight('module_admin', $xoops->module->getVar('mid'), $xoops->user->getGroups())) {
        $xoops->redirect($xoops_url, 1, XoopsLocale::E_NO_ACCESS_PERMISSION);
    }
} else {
    $xoops->redirect($xoops_url . '/user.php', 1, XoopsLocale::E_NO_ACCESS_PERMISSION);
}

// set config values for this module
if ($xoops->module->getVar('hasconfig') == 1 || $xoops->module->getVar('hascomments') == 1) {
    $xoops->moduleConfig = $xoops->getModuleConfigs();
}

// include the default language file for the admin interface
$xoops->loadLanguage('admin', $xoops->module->getVar('dirname'));
$xoops->moduleDirname = $xoops->module->getVar('dirname');
$xoops->isAdminSide = true;
