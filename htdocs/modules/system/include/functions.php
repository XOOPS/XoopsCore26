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
 * Blocks admin Manager
 *
 * @copyright   XOOPS Project (http://xoops.org)
 * @license     GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package     system
 * @version     $Id$
 */

/**
 * Get variables passed by GET or POST method
 *
 * @deprecated
 *
 * @param $global
 * @param $key
 * @param string $default
 * @param string $type
 * @return int|mixed|string
 */
function system_cleanVars(&$global, $key, $default = '', $type = 'int')
{
    switch ($type) {
        case 'array':
            $ret = (isset($global[$key]) && is_array($global[$key])) ? $global[$key] : $default;
            break;
        case 'date':
            $ret = (isset($global[$key])) ? strtotime($global[$key]) : $default;
            break;
        case 'string':
            $ret = (isset($global[$key])) ? filter_var($global[$key], FILTER_SANITIZE_MAGIC_QUOTES) : $default;
            break;
        case 'int':
        default:
            $ret = (isset($global[$key])) ? filter_var($global[$key], FILTER_SANITIZE_NUMBER_INT) : $default;
            break;
    }
    if ($ret === false) {
        return $default;
    }
    return $ret;
}

/**
 * System language loader wrapper
 *
 *
 * @param   string  $name       Name of language file to be loaded, without extension
 * @param   string  $domain     Module dirname; global language file will be loaded if $domain is set to 'global' or not specified
 * @param   string  $language   Language to be loaded, current language content will be loaded if not specified
 * @return  boolean
 * @todo    expand domain to multiple categories, e.g. module:system, framework:filter, etc.
 *
 */
function system_loadLanguage($name, $domain = '', $language = null)
{
    $xoops = Xoops::getInstance();
    /**
     * We must check later for an empty value. As xoops_getPageOption could be empty
     */
    if (empty($name)) {
        return false;
    }
    //for languages already moved into locale
    $done = array('preferences', 'blocksadmin', 'extensions', 'modulesadmin', 'groups', 'tplsets', 'users');
    if (in_array($name, $done)) {
        return false;
    }
    $language = empty($language) ? $xoops->getConfig('language') : $language;
    $path = 'modules/' . $domain . '/language/';
    if (XoopsLoad::fileExists($file = $xoops->path($path . $language . '/admin/' . $name . '.php'))) {
        $ret = include_once $file;
    } else {
        $ret = include_once $xoops->path($path . 'english/admin/' . $name . '.php');
    }
    return $ret;
}

/**
 * @param string $version
 * @param string $value
 * @return string
 */
function system_adminVersion($version, $value = '')
{
    static $tblVersion = array();
    if (is_array($tblVersion) && array_key_exists($version . '.' . $value, $tblVersion)) {
        return $tblVersion[$version . '.' . $value];
    }
    $xoops = Xoops::getInstance();
    $path = $xoops->path('modules/system/admin/' . $version . '/xoops_version.php');
    if (XoopsLoad::fileExists($path)) {
        $modversion = array();
        include $path;
        $retvalue = $modversion[$value];
        $tblVersion[$version . '.' . $value] = $retvalue;
        return $retvalue;
    }
    return '';
}

/**
 * @param string $img
 * @return string
 */
function system_AdminIcons($img)
{
    $xoops = Xoops::getInstance();
    $style = 'default';

    $url = $xoops->url('modules/system/images/icons/' . $style . '/' . $img);
    return $url;
}

/**
 * Give the page's AJAX scripts (admin.js, blocks.js, module.js) the token
 * their state changes send, as the JS global XOOPS_TOKEN
 *
 * @return void
 */
function system_addAjaxToken()
{
    static $added = false;
    if (!$added) {
        $xoops = Xoops::getInstance();
        $token = json_encode($xoops->security()->createToken(3600));
        $xoops->theme()->addScript('', array('type' => 'text/javascript'), 'var XOOPS_TOKEN = ' . $token . ';');
        $added = true;
    }
}

/**
 * @param string $name
 * @return void
 */
function system_loadTemplate($name)
{
    $xoops = Xoops::getInstance();

    $path = $xoops->path('modules/' . $xoops->module->getVar('dirname', 'n') . '/templates/admin/' . $name . '.tpl');
    if (XoopsLoad::fileExists($path)) {
        echo $xoops->tpl()->fetch($path);
    } else {
        echo "Unable to read " . $name;
    }
}

/**
 * @param int $value_chmod
 * @param string $path_file
 * @param string $id
 * @return string
 */
function modify_chmod($value_chmod, $path_file, $id)
{
    $chmod = '<div id="loading_' . $id . '" align="center" style="display:none;">' . '<img src="./images/mimetypes/spinner.gif" title="Loading" alt="Loading" width="12px"/></div>' . '<div id="chmod' . $id . '">' . '<select size="1" onChange="filemanager_modify_chmod(\'' . $path_file . '\', \'' . $id . '\')" name="chmod" id="chmod">';
    if ($value_chmod == 777) {
        $chmod .= '<option value="777" selected><span style="color:green">777</span></option>';
    } else {
        $chmod .= '<option value="777"><span style="color:green">777</span></option>';
    }

    if ($value_chmod == 776) {
        $chmod .= '<option value="776" selected>776</option>';
    } else {
        $chmod .= '<option value="776">776</option>';
    }

    if ($value_chmod == 766) {
        $chmod .= '<option value="766" selected>766</option>';
    } else {
        $chmod .= '<option value="766">766</option>';
    }

    if ($value_chmod == 666) {
        $chmod .= '<option value="666" selected>666</option>';
    } else {
        $chmod .= '<option value="666">666</option>';
    }

    if ($value_chmod == 664) {
        $chmod .= '<option value="664" selected>664</option>';
    } else {
        $chmod .= '<option value="664">664</option>';
    }

    if ($value_chmod == 644) {
        $chmod .= '<option value="644" selected>644</option>';
    } else {
        $chmod .= '<option value="644">644</option>';
    }

    if ($value_chmod == 444) {
        $chmod .= '<option value="444" selected><span style="color:red">444</span></option>';
    } else {
        $chmod .= '<option value="444">444</option>';
    }

    if ($value_chmod == 440) {
        $chmod .= '<option value="440" selected>440</option>';
    } else {
        $chmod .= '<option value="440">440</option>';
    }

    if ($value_chmod == 400) {
        $chmod .= '<option value="400" selected>400</option>';
    } else {
        $chmod .= '<option value="400">400</option>';
    }
    $chmod .= '</select>';

    return $chmod;
}
