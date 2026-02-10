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
 * Installer db inserting page
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
 */

require_once __DIR__ . '/include/common.inc.php';


$xoops = Xoops::getInstance();

// setup legacy db support
$GLOBALS['xoopsDB'] = \XoopsDatabaseFactory::getDatabaseConnection(true);

/* @var $wizard XoopsInstallWizard */
$wizard = $_SESSION['wizard'];

$settings = $_SESSION['settings'];

$language = $wizard->language;
$xoops->setConfig('locale', $language);
$xoops->loadLocale();

$dbm = $xoops->db();
$count = $dbm->fetchOne('SELECT COUNT(*) FROM ' . $dbm->prefix('system_user'));
$process = $count ? false : true;
$update = false;

$siteconfig = $_SESSION['siteconfig'];
$adminname = $siteconfig['adminname'];
$adminpass = $siteconfig['adminpass'];
$adminmail = $siteconfig['adminmail'];


$wizard->loadLangFile('install2');
$temp = password_hash($adminpass, PASSWORD_DEFAULT);
$regdate = time();
if ($process) {
    $dbm->insertPrefix(
        'system_user',
        array(
            //'uid'             => 1,             // mediumint(8) unsigned NOT NULL auto_increment,
            'name'            => '',            // varchar(60) NOT NULL default '',
            'uname'           => $adminname,    // varchar(25) NOT NULL default '',
            'email'           => $adminmail,    // varchar(60) NOT NULL default '',
            'url'             => XOOPS_URL,     // varchar(100) NOT NULL default '',
            'user_avatar'     => 'blank.gif',   // varchar(30) NOT NULL default 'blank.gif',
            'user_regdate'    => $regdate,      // int(10) unsigned NOT NULL default '0',
            'user_icq'        => '',            // varchar(15) NOT NULL default '',
            'user_from'       => '',            // varchar(100) NOT NULL default '',
            'user_sig'        => '',            // tinytext,
            'user_viewemail'  => 1,             // tinyint(1) unsigned NOT NULL default '0',
            'actkey'          => '',            // varchar(8) NOT NULL default '',
            'user_aim'        => '',            // varchar(18) NOT NULL default '',
            'user_yim'        => '',            // varchar(25) NOT NULL default '',
            'user_msnm'       => '',            // varchar(100) NOT NULL default '',
            'pass'            => $temp,         // varchar(255) NOT NULL default '',
            'posts'           => 0,             // mediumint(8) unsigned NOT NULL default '0',
            'attachsig'       => 0,             // tinyint(1) unsigned NOT NULL default '0',
            'rank'            => 7,             // smallint(5) unsigned NOT NULL default '0',
            'level'           => 5,             // tinyint(3) unsigned NOT NULL default '1',
            'theme'           => 'default',     // varchar(100) NOT NULL default '',
            'timezone'        => 'UTC',         // varchar(32) NOT NULL default 'UTC',
            'last_login'      => $regdate,      // int(10) unsigned NOT NULL default '0',
            'last_pass_change'=> $regdate,      // int(10) unsigned NOT NULL default '0',
            'umode'           => 'flat',        // varchar(10) NOT NULL default '',
            'uorder'          => 0,             // tinyint(1) unsigned NOT NULL default '0',
            'notify_method'   => 1,             // tinyint(1) NOT NULL default '1',
            'notify_mode'     => 0,             // tinyint(1) NOT NULL default '0',
            'user_occ'        => '',            // varchar(100) NOT NULL default '',
            'bio'             => '',            // tinytext,
            'user_intrest'    => '',            // varchar(150) NOT NULL default '',
            'user_mailok'     => 0,             // tinyint(1) unsigned NOT NULL default '1',
        )
    );
    $content = '<div class="x2-note successMsg">' . DATA_INSERTED . '</div>';
} elseif ($update) {
    $dbm->updatePrefix(
        'system_user',
        array(
            'uname' => $adminname,
            'email' => $adminmail,
            'user_regdate' => $regdate,
            'pass' => $temp,
            'last_login' => $regdate,
            'last_pass_change'=> $regdate,
        ),
        array(
            'id' => 1,
        )
    );
    $content = '';
} else {
    $content = "<div class='x2-note confirmMsg'>" . DATA_ALREADY_INSERTED . "</div>";
}

setcookie('xo_install_user', '', 0, '', '');
if (isset( $settings['authorized'] ) && !empty($adminname) && !empty($adminpass)) {
    setcookie(
        'xo_install_user',
        addslashes($adminname) . '-' . md5($temp . XOOPS_DB_NAME . XOOPS_DB_PASS . XOOPS_DB_PREFIX),
        0,
        '',
        ''
    );
}

$_SESSION['pageHasHelp'] = false;
$_SESSION['pageHasForm'] = false;
$_SESSION['content'] = $content;
include XOOPS_INSTALL_PATH . '/include/install_tpl.php';
