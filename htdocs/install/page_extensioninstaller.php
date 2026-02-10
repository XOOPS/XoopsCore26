<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

use Xoops\Core\Kernel\Handlers\XoopsModule;

/**
 * @copyright   XOOPS Project (http://xoops.org)
 * @license     GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package     installer
 * @author      Andricq Nicolas (AKA MusS)
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
    $xoops->loadLocale('system');
    include_once XOOPS_ROOT_PATH . "/modules/system/class/module.php";
    include_once XOOPS_ROOT_PATH . "/modules/system/class/system.php";

    $system = System::getInstance();

    $system_module = new SystemModule();

    $msgs = array();
    foreach ($_REQUEST['modules'] as $dirname => $installmod) {
        if ($installmod) {
            $msgs[] = $system_module->install($dirname);
        }
    }

    $pageHasForm = false;

    if (count($msgs) > 0) {
        $content = "<div class='x2-note successMsg'>" . INSTALLED_EXTENSION . "</div><ul class='log'>";
        foreach ($msgs as $msg) {
            if ($msg instanceof XoopsModule) {
                $tempvar = $msg->modinfo['name'];
                $content .= "<dt>{$tempvar}</dt>";
            }
        }
        $content .= "</ul>";
    } else {
        $content = "<div class='x2-note confirmMsg'>" . NO_INSTALLED_EXTENSION . "</div>";
    }

    //Reset module lists in cache folder
    $xoops->cache()->delete('system/modules');
    $xoops->setActiveModules();
} else {
    if (!$xoops->getConfig('locale')) {
        $xoops->setConfig('locale', $_COOKIE['xo_install_lang']);
    }
    $xoops->loadLocale('system');
    include_once XOOPS_ROOT_PATH . "/modules/system/class/module.php";
    include_once XOOPS_ROOT_PATH . "/modules/system/class/extension.php";
    include_once XOOPS_ROOT_PATH . "/modules/system/class/system.php";

    $system = System::getInstance();

    // Get installed modules
    $system_module = new SystemExtension();

    $dirlist = $system_module->getInstalledExtensions();
    $toinstal = 0;

    $javascript = "";
    $content = "<ul class='log'><li style='background: none;'>";
    $content .= "<table class='module'>\n";
    /* @var $ext XoopsModule */
    foreach ($dirlist as $ext) {
        clearstatcache();
        $value = 0;
        $style = "";

        if (in_array($ext->getInfo('dirname'), $wizard->configs['ext'])) {
            $value = 1;
            $style = " style='background-color:#E6EFC2;'";
        }

        $form = new Xoops\Form\ThemeForm('', 'modules', 'index.php', 'post');
        $moduleYN = new Xoops\Form\RadioYesNo('', 'modules[' . $ext->getInfo('dirname') . ']', $value, XoopsLocale::YES, XoopsLocale::NO);
        $moduleYN->setExtra("onclick='selectModule(\"" . $ext->getInfo('dirname') . "\", this)'");
        $form->addElement($moduleYN);

        $content .= "<tr id='" . $ext->getInfo('dirname') . "'" . $style . ">\n";
        $content .= "    <td class='img' ><img src='" . XOOPS_URL . "/modules/" . $ext->getInfo('dirname') . "/" . $ext->getInfo('image') . "' alt='" . $ext->getInfo('name') . "'/></td>\n";
        $content .= "    <td>";
        $content .= "        " . $ext->getInfo('name') . "&nbsp;" . number_format(round((float)$ext->getInfo('version'), 2), 2) . "&nbsp;(" . $ext->getInfo('dirname') . ")";
        $content .= "        <br />" . $ext->getInfo('description');
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
        $content = "<div class='x2-note confirmMsg'>" . NO_EXTENSION_FOUND . "</div>";
    }
}
$_SESSION['pageHasHelp'] = false;
$_SESSION['pageHasForm'] = $pageHasForm;
$_SESSION['content'] = $content;
include XOOPS_INSTALL_PATH . '/include/install_tpl.php';
