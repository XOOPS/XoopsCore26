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
 * @copyright   XOOPS Project (http://xoops.org)
 * @license     GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package     installer
 * @since       2.3.0
 * @author      Haruki Setoyama  <haruki@planewave.org>
 * @author      Kazumi Ono <webmaster@myweb.ne.jp>
 * @author      Skalpa Keo <skalpa@xoops.org>
 * @author      Taiwen Jiang <phppp@users.sourceforge.net>
 * @author      DuGris (aka L. JEN) <dugris@frxoops.org>
 * @version     $Id$
 */

$xoopsOption['checkadmin'] = true;
$xoopsOption['hascommon'] = true;

require_once __DIR__ . '/include/common.inc.php';

$xoops = Xoops::getInstance();

/* @var $wizard XoopsInstallWizard */
$wizard = $_SESSION['wizard'];
$pageHasForm = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $xoops->events();
    $xoops->loadLocale('system');
    include_once XOOPS_ROOT_PATH . "/modules/system/class/module.php";
    include_once XOOPS_ROOT_PATH . "/modules/system/class/system.php";

    $system_module = new SystemModule();
    $system = System::getInstance();

    $msgs = array();
    foreach ($_REQUEST['modules'] as $dirname => $installmod) {
        if ($installmod) {
            $msgs[] = $system_module->install($dirname);
        }
    }

    $pageHasForm = false;

    if (count($msgs) > 0) {
        $content = "<div class='x2-note successMsg'>" . INSTALLED_MODULES . "</div><ul class='log'>";
        foreach ($msgs as $msg) {
            $tempvar = $msg->modinfo['name'];
            $content .= "<dt>{$tempvar}</dt>";
        }
        $content .= "</ul>";
    } else {
        $content = "<div class='x2-note confirmMsg'>" . NO_INSTALLED_MODULES . "</div>";
    }

    //Reset module lists in cache folder
    $xoops->cache()->delete('system/modules');
    $dirnames = $xoops->setActiveModules();

    /*
     * With multiple module installs in one pass, preload events are not refreshed in between.
     * One side effect is that plugins that modify configurations (specifically comments and notifications)
     * do not get a chance to process the install events as is needed. Here, we will loop through all
     * the modules supplying a plugin interface and update them, reprocessing and fixing everything.
     */
    \Xoops\Module\Plugin::resetPluginsCache();
    $xoops->setTpl(new Xoops\Core\XoopsTpl()); // update() uses this???
    foreach ($dirnames as $dirname) {
        if ($dirname !== 'system') {
            if (\XoopsLoad::fileExists($xoops->path("modules/{$dirname}/class/plugin/interface.php"))) {
                $xoops->clearModuleConfigsCache();
                $system_module->update($dirname);
            }
        }
    }

} else {
    if (!$xoops->getConfig('locale')) {
        $xoops->setConfig('locale', $_COOKIE['xo_install_lang']);
    }
    $xoops->loadLocale('system');

    include_once XOOPS_ROOT_PATH . "/modules/system/class/module.php";
    include_once XOOPS_ROOT_PATH . "/modules/system/class/system.php";

    $system = System::getInstance();
    // Get installed modules
    $system_module = new SystemModule();

    $dirlist = $system_module->getInstalledModules();
    $toinstal = 0;

    $javascript = "";
    $content = "<ul class='log'><li style='background: none;'>";
    $content .= "<table class='module'>\n";
    /* @var $module XoopsModule */
    foreach ($dirlist as $module) {
        clearstatcache();
        $value = 0;
        $style = "";
        if (in_array($module->getInfo('dirname'), $wizard->configs['modules'])) {
            $value = 1;
            $style = " style='background-color:#E6EFC2;'";
        }
        $form = new Xoops\Form\ThemeForm('', 'modules', 'index.php', 'post');
        $moduleYN = new Xoops\Form\RadioYesNo('', 'modules[' . $module->getInfo('dirname') . ']', $value, XoopsLocale::YES, XoopsLocale::NO);
        $moduleYN->set('onclick', 'selectModule("' . $module->getInfo('dirname') . '", this);');
        $form->addElement($moduleYN);

        $content .= "<tr id='" . $module->getInfo('dirname') . "'" . $style . ">\n";
        $content .= "    <td class='img' ><img src='" . XOOPS_URL . "/modules/" . $module->getInfo('dirname') . "/" . $module->getInfo('image') . "' alt='" . $module->getInfo('name') . "'/></td>\n";
        $content .= "    <td>";
        $content .= "        " . $module->getInfo('name') . "&nbsp;" . number_format(round((float)$module->getInfo('version'), 2), 2) . "&nbsp;(" . $module->getInfo('dirname') . ")";
        $content .= "        <br />" . $module->getInfo('description');
        $content .= "    </td>\n";
        $content .= "    <td class='yesno'>";
        $content .= $moduleYN->render();
        $content .= "    </td></tr>\n";
        ++$toinstal;
    }
    $content .= "</table>";
    $content .= "</li></ul><script type='text/javascript'>" . $javascript . "</script>";
    if ($toinstal == 0) {
        $pageHasForm = false;
        $content = "<div class='x2-note confirmMsg'>" . NO_MODULES_FOUND . "</div>";
    }
}

$_SESSION['pageHasHelp'] = false;
$_SESSION['pageHasForm'] = $pageHasForm;
$_SESSION['content'] = $content;
include XOOPS_INSTALL_PATH . '/include/install_tpl.php';
