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
 * Installer language selection page
 *
 * @copyright   XOOPS Project (http://xoops.org)
 * @license     GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package     installer
 * @since       2.3.0
 * @author      Haruki Setoyama  <haruki@planewave.org>
 * @author      Kazumi Ono <webmaster@myweb.ne.jp>
 * @author      Skalpa Keo <skalpa@xoops.org>
 * @author      Taiwen Jiang <phppp@users.sourceforge.net>
 * @author      DuGris <dugris@frxoops.org>
 * @author      DuGris (aka L. JEN) <dugris@frxoops.org>
 * @version     $Id$
 */

require_once __DIR__ . '/include/common.inc.php';

/* @var $wizard XoopsInstallWizard */
$wizard = $_SESSION['wizard'];
$_SESSION['settings'] = array();

setcookie('xo_install_lang', 'en_US', 0, '', '');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_REQUEST['lang'])) {
    $lang = $_REQUEST['lang'];
    setcookie('xo_install_lang', $lang, 0, '', '');

    $wizard->redirectToPage('+1');
    exit();
}
$_SESSION['settings'] = array();

setcookie('xo_install_user', '', 0, '', '');

//$title = LANGUAGE_SELECTION;
$content = '<div class="languages">';

$languages = getDirList("./locale/");
foreach ($languages as $lang) {
    $sel = ($lang == $wizard->language) ? ' checked="checked"' : '';
    $content .= "<label><input type=\"radio\" name=\"lang\" value=\"{$lang}\"{$sel} />{$lang}</label>\n";
}

$content .= "</div>";

$_SESSION['pageHasHelp'] = false;
$_SESSION['pageHasForm'] = true;
$_SESSION['content'] = $content;
include XOOPS_INSTALL_PATH . '/include/install_tpl.php';
