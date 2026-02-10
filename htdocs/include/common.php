<?php
/**
 * XOOPS common initialization file
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package   kernel
 */

use Xoops\Core\FixedGroups;
use Xoops\Core\Kernel\Handlers\XoopsUser;
use Xoops\Core\Kernel\Criteria;
use Xoops\Core\Kernel\CriteriaCompo;


/**
 * Include XoopsLoad - this should have been done in mainfile.php, but there is
 * no update yet, so only only new installs get the change in mainfile.dist.php
 * automatically.
 *
 * Temporarily try and fix, but set up a (delayed) warning
 */
if (!class_exists('XoopsLoad', false)) {
    require_once dirname(__DIR__). '/class/XoopsBaseConfig.php';
    XoopsBaseConfig::bootstrapTransition();
    $delayedWarning = 'Patch mainfile.php for XoopsBaseConfig';
}

global $xoops;
$GLOBALS['xoops'] =& $xoops;

//Legacy support
global $xoopsDB;
$GLOBALS['xoopsDB'] =& $xoopsDB;
/**
 * YOU SHOULD NEVER USE THE FOLLOWING TO CONSTANTS, THEY WILL BE REMOVED
 */
defined('DS') or define('DS', DIRECTORY_SEPARATOR);
defined('NWLINE')or define('NWLINE', "\n");

/**
 * Include files with definitions
 */
include_once __DIR__ . '/defines.php';
// include_once __DIR__ . '/version.php';

/**
 * Configure PHP for UTF-8
 * Previously used Patchwork\Utf8\Bootup which is abandoned.
 * PHP 8.x defaults to UTF-8 for most mbstring functions, but we set it explicitly.
 */
mb_internal_encoding('UTF-8');
mb_regex_encoding('UTF-8');

/**
 * Create Instance of Xoops Object
 * Attention, not all methods can be used at this point
 */
$xoops = Xoops::getInstance();

$xoops->option =& $GLOBALS['xoopsOption'];

/**
 * Create Instance Xoops\Core\Logger Object, the logger manager
 */
$xoopsLogger = $xoops->logger();

/**
 * initialize events
 */
$xoops->events();

$psr4loader = new \Xoops\Core\Psr4ClassLoader();
$psr4loader->register();
// listeners should respond with "$arg->addNamespace($namespace, $directory);"
$xoops->events()->triggerEvent('core.include.common.psr4loader', $psr4loader);
$xoops->events()->triggerEvent('core.include.common.classmaps');
// add autoloader for any $modversion['namespace'] to dirname/src
$key = 'system/modules/autoload';
//$xoops->cache()->delete($key);
$autoloadPrefixDirList = $xoops->cache()->cacheRead(
    $key,
    function () use ($xoops) {
        try {
            $criteria = new CriteriaCompo(new Criteria('isactive', 1));
            $criteria->add(new Criteria('namespace', '', '!='));
            $moduleHandler = $xoops->getHandlerModule();
            $autoloadModules = $moduleHandler->getObjectsArray($criteria);
        } catch (\Throwable $e) {
            return [];
        }
        $autoloadPrefixDirList = [];
        /** @var XoopsObject $almod */
        foreach ($autoloadModules as $almod) {
            $prefix = $almod->getVar('namespace');
            $base_dir = $xoops->path('modules/' . $almod->getVar('dirname') . '/src');
            $autoloadPrefixDirList[] = [$prefix, $base_dir];
        }
        return $autoloadPrefixDirList;
    }
);
foreach ($autoloadPrefixDirList as $almod) {
    $psr4loader->addNamespace($almod[0], $almod[1]);
}

/**
 * Create Instance of xoopsSecurity Object and check super globals
 */
$xoops->events()->triggerEvent('core.include.common.security');
$xoopsSecurity = $xoops->security();

/**
 * Check Proxy;
 * Requires functions
 */

if (!defined('XOOPS_XMLRPC')) {
    define('XOOPS_DB_CHKREF', 1);
} else {
    define('XOOPS_DB_CHKREF', 0);
}

if ('POST' !== \Xmf\Request::getMethod() || !$xoopsSecurity->checkReferer(XOOPS_DB_CHKREF)) {
    define('XOOPS_DB_PROXY', 1);
}

/**
 * Get database for making it global
 * Will also setup $xoopsDB for legacy support.
 * Requires XOOPS_DB_PROXY;
 */
$xoops->db();
//For Legacy support
$xoopsDB = \XoopsDatabaseFactory::getDatabaseConnection();

$xoops->events()->triggerEvent('core.include.common.start');

/**
 * temporary warning message
 */
if (isset($delayedWarning)) {
    trigger_error($delayedWarning);
}

/**
 * Include Required Files not handled by autoload
 */
include_once $xoops->path('include/functions.php');

/**
 * Get xoops configs
 * Requires functions and database loaded
 */
$xoops->getConfigs();
$xoopsConfig =& $xoops->config;

/**
 * Merge file and db configs.
 */
if (XoopsLoad::fileExists($file = $xoops->path('var/configs/system_configs.php'))) {
    $xoops->addConfigs(include $file);
    unset($file);
} else {
    trigger_error('File Path Error: ' . 'var/configs/system_configs.php' . ' does not exist.');
}

$xoops->events()->triggerEvent('core.include.common.configs.success');

/**
 * Enable Gzip compression,
 * Requires configs loaded and should go before any output
 */
$xoops->gzipCompression();

/**
 * clickjack protection - Add option to HTTP header restricting using site in an iframe
 */
$xFrameOptions = $xoopsConfig['xFrameOptions'] ?? 'sameorigin';
$xoops->events()->triggerEvent('core.include.common.xframeoption');
if (!headers_sent() && !empty($xFrameOptions)) {
    header('X-Frame-Options: ' .$xFrameOptions);
}

/**
 * Check Bad Ip Addressed against database and block bad ones, requires configs loaded
 */
$xoops->security()->checkBadips();

/**
 * Load Language settings and defines
 */
$xoops->loadLocale();
//For legacy
$xoops->setConfig('language', XoopsLocale::getLegacyLanguage());

/**
 * User Sessions
 */
$member_handler = $xoops->getHandlerMember();

$xoops->session()->sessionStart();

/**
 * Gather some info about the logged in user
 */
if ($xoops->session()->has('xoopsUserId')) {
    $uid = $xoops->session()->get('xoopsUserId');
    $xoops->user = $member_handler->getUser($uid);
    if ($xoops->user instanceof XoopsUser) {
        if (((int)($xoops->user->getVar('last_login')) + 60 * 5) < time()) {
            $user_handler = $xoops->getHandlerUser();
            $criteria = new Criteria('uid', $uid);
            $user_handler->updateAll('last_login', time(), $criteria, true);
            unset($criteria);
        }
        $xoops->userIsAdmin = $xoops->user->isAdmin();
    }
}

date_default_timezone_set(XoopsLocale::getTimezone());
setlocale(LC_ALL, XoopsLocale::getLocale());

$xoops->events()->triggerEvent('core.include.common.auth.success');

/**
 * Theme Selection
 */
$xoops->themeSelect();

/**
 * Closed Site
 */
if ($xoops->getConfig('closesite') == 1) {
    include_once $xoops->path('include/site-closed.php');
}

/**
 * Load Xoops Module
 */
$xoops_url = \XoopsBaseConfig::get('url');
$xoops->moduleDirname = 'system';
if (XoopsLoad::fileExists('./xoops_version.php')) {
    $url_arr = explode('/', strstr($_SERVER['PHP_SELF'], '/modules/'));
    $module_handler = $xoops->getHandlerModule();
    $xoops->module = $xoops->getModuleByDirname($url_arr[2]);
    $xoops->moduleDirname = $url_arr[2];
    unset($url_arr);

    if (!$xoops->module || !$xoops->module->getVar('isactive')) {
        $xoops->redirect($xoops_url, 3, XoopsLocale::E_NO_MODULE);
        exit();
    }
    $moduleperm_handler = $xoops->getHandlerGroupPermission();
    if ($xoops->isUser()) {
        if (!$moduleperm_handler->checkRight('module_read', $xoops->module->getVar('mid'), $xoops->user->getGroups())) {
            $xoops->redirect($xoops_url, 1, XoopsLocale::E_NO_ACCESS_PERMISSION, false);
        }
        $xoops->userIsAdmin = $xoops->user->isAdmin($xoops->module->getVar('mid'));
    } else {
        if (!$moduleperm_handler->checkRight('module_read', $xoops->module->getVar('mid'), FixedGroups::ANONYMOUS)) {
            $xoops->redirect(
                $xoops_url . '/user.php?from=' . $xoops->module->getVar('dirname', 'n'),
                1,
                XoopsLocale::E_NO_ACCESS_PERMISSION
            );
        }
    }

    if ($xoops->module->getVar('dirname', 'n') !== 'system') {
        $xoops->loadLanguage('main', $xoops->module->getVar('dirname', 'n'));
        $xoops->loadLocale($xoops->module->getVar('dirname', 'n'));
    }

    if ($xoops->module->getVar('hasconfig') == 1
        || $xoops->module->getVar('hascomments') == 1
        || $xoops->module->getVar('hasnotification') == 1
    ) {
        $xoops->getModuleConfigs();
    }
} else {
    if ($xoops->isUser()) {
        $xoops->userIsAdmin = $xoops->user->isAdmin(1);
    }
}

$xoopsTpl = $xoops->tpl();
$xoTheme = null;
$xoopsUser =& $xoops->user;
$xoopsModule =& $xoops->module;
$xoopsUserIsAdmin =& $xoops->userIsAdmin;
$xoopsModuleConfig =& $xoops->moduleConfig;

//Creates 'system_modules_active' cache file if it has been deleted.
$xoops->getActiveModules();

$xoops->events()->triggerEvent('core.include.common.end');
