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
// released at exit), so concurrent sessions cannot both pass the check; without
// the lock nothing is written.
// An empty file is a first-install stub, not a configuration. A non-empty
// secure.php is guarded even without mainfile.php: VAR_PATH comes from this
// session and may point at another site's data directory.
$configLock = @fopen(__FILE__, 'r');
$locked = $configLock !== false && flock($configLock, LOCK_EX);
// secure.php first: mainfile.php requires it, so it is written only once
// secure.php is in place, and the first failure stops the step
$configFiles = array(
    'secure' => $settings['VAR_PATH'] . '/data/secure.php',
    'mainfile' => $settings['ROOT_PATH'] . '/mainfile.php',
);
$owned = (isset($_SESSION['configsave_owner']) && is_array($_SESSION['configsave_owner']))
    ? $_SESSION['configsave_owner'] : array();
// keyed by real path, so an identical copy of a file elsewhere is not owned
$ownsFile = function ($path) use (&$owned) {
    $real = realpath($path);
    $hash = $real === false ? false : hash_file('sha256', $real);
    return $hash !== false && isset($owned[$real]) && hash_equals((string) $owned[$real], $hash);
};
$foreign = defined('XOOPS_MAINFILE_INCLUDED') && empty($owned);
foreach ($configFiles as $path) {
    if (is_file($path) && filesize($path) > 0 && !$ownsFile($path)) {
        $foreign = true;
    }
}
if ($foreign || !$locked) {
    if ($foreign) {
        unset($_SESSION['configsave_owner']);
    }
    $_SESSION['pageHasHelp'] = false;
    $_SESSION['pageHasForm'] = false;
    $_SESSION['content'] = '<div class="errorMsg">' . ($foreign ? ERR_ALREADY_INSTALLED : ERR_WRITE_MAINFILE) . '</div>';
    include_once XOOPS_INSTALL_PATH . '/include/install_tpl.php';
    exit();
}
$error = '';

$rewrite = array(
    'GROUP_ADMIN' => 1, 'GROUP_USERS' => 2, 'GROUP_ANONYMOUS' => 3
);
$rewrite = array_merge($rewrite, $settings);

$configErrors = array(
    'mainfile' => array(ERR_COPY_MAINFILE, ERR_WRITE_MAINFILE),
    'secure' => array(ERR_COPY_SECURE . $settings['VAR_PATH'] . '/data/secure.dist.php', ERR_WRITE_SECURE),
);
foreach ($configFiles as $key => $path) {
    list($errCopy, $errWrite) = $configErrors[$key];
    $content = @file_get_contents(substr($path, 0, -4) . '.dist.php');
    if ($content === false) {
        $error = $errCopy;
        break;
    }
    $content = install_rewriteDefines($content, $rewrite);
    $hash = hash('sha256', $content);
    if ($ownsFile($path) && hash_file('sha256', $path) === $hash) {
        continue; // a retry: this session already wrote exactly this file
    }
    // write a new file beside the target and rename it over it, so a failed
    // attempt leaves the previous file (and its ownership) untouched; without a
    // writable directory only an empty stub is written in place, and emptied
    // again if the write fails
    $tmp = $path . '.' . bin2hex(random_bytes(8)) . '.tmp';
    $handle = @fopen($tmp, 'xb');
    if ($handle !== false) {
        if (is_file($path)) {
            @chmod($tmp, fileperms($path) & 0777); // keep a mode the admin tightened
        }
        $saved = fwrite($handle, $content) === strlen($content);
        $saved = fclose($handle) && $saved && @rename($tmp, $path);
        if (!$saved) {
            @unlink($tmp);
        }
    } elseif (is_file($path) && filesize($path) === 0 && ($handle = @fopen($path, 'cb')) !== false) {
        $saved = fwrite($handle, $content) === strlen($content) && fflush($handle);
        if (!$saved) {
            ftruncate($handle, 0);
        }
        $saved = fclose($handle) && $saved;
    } else {
        $saved = false;
    }
    if ($saved && ($real = realpath($path)) !== false) {
        $owned[$real] = $hash;
        $_SESSION['configsave_owner'] = $owned;
        continue;
    }
    $error = $errWrite;
    break;
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
