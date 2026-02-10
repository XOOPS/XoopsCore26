<?php

if (defined('XOOPS_TU_ROOT_PATH')) return;

if (version_compare(PHP_VERSION, '8.4.0', '<')) {
    die("XOOPS check: PHP version requires 8.4.0 or higher");
}

// needed for phpunit => initializing $_SERVER values
if (empty($_SERVER["HTTP_HOST"])) {
    define('IS_PHPUNIT', true);
}

if (defined('IS_PHPUNIT')) {
    require_once __DIR__ . '/common_phpunit.php';
} else {
    // Avoid check proxy to define constant XOOPS_DB_PROXY
    // because it implies a readonly database connection
    $_SERVER['REQUEST_METHOD'] = 'POST';
    define('XOOPS_XMLRPC', 0);
}

define('XOOPS_TU_ROOT_PATH', realpath(dirname(__FILE__).'/../../htdocs'));

//temporary patch, we still need mainfile until we have a config file
$xoopsOption["nocommon"]= true; // don't include common.php file
require_once(XOOPS_TU_ROOT_PATH . '/mainfile.php');
//require_once(XOOPS_TU_ROOT_PATH . '/class/XoopsBaseConfig.php');

\XoopsBaseConfig::bootstrapTransition();

\Xoops\Locale::loadLocale();
