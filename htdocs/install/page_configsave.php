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
 * Installer mainfile creation page
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

/* @var $wizard XoopsInstallWizard */
$wizard = $_SESSION['wizard'];

$settings = $_SESSION['settings'];

if (empty($settings['ROOT_PATH'])) {
    $wizard->redirectToPage('pathsettings');
    exit();
} elseif (empty($settings['DB_PARAMETERS'])) {
    $wizard->redirectToPage('dbdriver');
    exit();
}

// A mainfile.php that this session did not write means the site is already
// installed: never let a new installer session rewrite its configuration.
// Ownership is the hash of each configuration file this session wrote, so it
// ends as soon as anyone else rewrites the file.
// The check and both writes run under one installer-wide lock (this script,
// released at exit), so concurrent sessions cannot both pass the check.
$configLock = fopen(__FILE__, 'r');
if ($configLock) {
    flock($configLock, LOCK_EX);
}
$configFiles = array(
    'mainfile' => $settings['ROOT_PATH'] . '/mainfile.php',
    'secure' => $settings['VAR_PATH'] . '/data/secure.php',
);
$owned = (isset($_SESSION['configsave_owner']) && is_array($_SESSION['configsave_owner']))
    ? $_SESSION['configsave_owner'] : array();
$foreign = defined('XOOPS_MAINFILE_INCLUDED') && empty($owned);
foreach ($configFiles as $key => $path) {
    if (file_exists($path)
        && !(isset($owned[$key]) && hash_equals((string) $owned[$key], (string) hash_file('sha256', $path)))
    ) {
        $foreign = true;
    }
}
if ($foreign) {
    unset($_SESSION['configsave_owner']);
    $_SESSION['pageHasHelp'] = false;
    $_SESSION['pageHasForm'] = false;
    $_SESSION['content'] = '<div class="errorMsg">' . ERR_ALREADY_INSTALLED . '</div>';
    include_once XOOPS_INSTALL_PATH . '/include/install_tpl.php';
    exit();
}
$error = '';

$rewrite = array(
    'GROUP_ADMIN' => 1, 'GROUP_USERS' => 2, 'GROUP_ANONYMOUS' => 3
);
$rewrite = array_merge($rewrite, $settings);

$configErrors = array(
    'mainfile' => array(ERR_COPY_MAINFILE, ERR_READ_MAINFILE, ERR_WRITE_MAINFILE),
    'secure' => array(ERR_COPY_SECURE . $settings['VAR_PATH'] . '/data/secure.dist.php', ERR_READ_SECURE, ERR_WRITE_SECURE),
);
foreach ($configFiles as $key => $path) {
    list($errCopy, $errRead, $errWrite) = $configErrors[$key];
    if (!@copy(substr($path, 0, -4) . '.dist.php', $path)) {
        $error = $errCopy;
        continue;
    }
    clearstatcache();
    $content = @file_get_contents($path);
    if ($content === false) {
        $error = $errRead;
    } else {
        $content = install_rewriteDefines($content, $rewrite);
        if (@file_put_contents($path, $content) === strlen($content)) {
            $owned[$key] = hash('sha256', $content);
            $_SESSION['configsave_owner'] = $owned;
            continue;
        }
        $error = $errWrite;
    }
    // remove the file this attempt left half-written, so a retry does not see it as foreign
    unset($owned[$key]);
    $_SESSION['configsave_owner'] = $owned;
    @unlink($path);
}

// update composer.json with our paths
/*
$composer_path = $settings['PATH'] . '/composer.json';
$composer = json_decode(file_get_contents($composer_path));
$xoops_modules_path = $settings['ROOT_PATH'] . '/modules/';
if (isset($composer->extra) && is_object($composer->extra)) {
    $composer->extra->xoops_modules_path = $xoops_modules_path;
} else {
    $composer->extra['xoops_modules_path'] = $xoops_modules_path;
}

$jsonEncodeOpts = JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE;
file_put_contents($composer_path, json_encode($composer, $jsonEncodeOpts));
*/
$settings['authorized'] = false;
if (empty($error)) {
    $_SESSION['UserLogin'] = true;
    $settings['authorized'] = true;
    $wizard->cleanCache($rewrite['VAR_PATH']);
    ob_start();
    ?>

<div class="caption"><?php echo SAVED_MAINFILE; ?></div>
<div class='x2-note confirmMsg'><?php echo SAVED_MAINFILE_MSG; ?></div>
<ul class='diags'>

    <?php
    foreach ($settings as $k => $v) {
    if ($k === 'authorized') {
        continue;
    }
    echo "<li><strong>XOOPS_{$k}</strong> " . IS_VALOR . ' ' . installHtmlSpecialCharacters((string) $v) . '</li>';
}
    ?>
</ul>
    <?php
        $content = ob_get_contents();
    ob_end_clean();
} else {
    $content = '<div class="errorMsg">' . $error . '</div>';
}

$_SESSION['pageHasHelp'] = false;
$_SESSION['pageHasForm'] = false;
$_SESSION['content'] = $content;
$_SESSION['settings'] = $settings;
include XOOPS_INSTALL_PATH . '/include/install_tpl.php';
