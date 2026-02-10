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
 * Installer final page
 *
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

require_once __DIR__ . '/include/common.inc.php';

$xoops = Xoops::getInstance();

/* @var $wizard XoopsInstallWizard */
$wizard = $_SESSION['wizard'];

setcookie('xo_install_user', '', 0, '', '');

$installer_modified = "install_remove_" . uniqid(mt_rand());
register_shutdown_function('install_finalize', $installer_modified);

$_SESSION['installer_modified'] = $installer_modified;

$wizard->loadLangFile('finish');
$content = $_SESSION['content'];

$_SESSION['pageHasHelp'] = false;
$_SESSION['pageHasForm'] = false;
$_SESSION['content'] = $content;
include XOOPS_INSTALL_PATH . '/include/install_tpl.php';
$_SESSION = array();
