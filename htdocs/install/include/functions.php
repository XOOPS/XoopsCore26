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

function installHtmlSpecialCharacters($value)
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * @param string $hash
 * @return bool
 */
function install_acceptUser($hash = '')
{
    $xoops = Xoops::getInstance();
    $xoops->user = null;
    $hash_data = @explode("-", $_COOKIE['xo_install_user'], 2);
    list($uname, $hash_login) = array($hash_data[0], (string)(@$hash_data[1]));
    if (empty($uname) || empty($hash_login)) {
        return false;
    }
    $member_handler = $xoops->getHandlerMember();
    /* @var $user XoopsUser */
    $users = $member_handler->getUsers(new Criteria('uname', $uname));
    $user = array_pop($users);
    if (!($user instanceof \Xoops\Core\Kernel\Handlers\XoopsUser)) {
        return false;
    }
    if ($hash_login != md5($user->getVar('pass') . XOOPS_DB_NAME . XOOPS_DB_PASS . XOOPS_DB_PREFIX)) {
        return false;
    }
    $xoops->user = $user;
    $xoops->userIsAdmin = true;
    $_SESSION['xoopsUserId'] = $xoops->user->getVar('uid');
    $_SESSION['xoopsUserGroups'] = $xoops->user->getGroups();
    return true;
}

/**
 * @param $installer_modified
 * @return void
 */
function install_finalize($installer_modified)
{
    // Set mainfile.php readonly
    @chmod(XOOPS_ROOT_PATH . "/mainfile.php", 0444);
    // Set Secure file readonly
    @chmod(XOOPS_VAR_PATH . "/data/secure.php", 0444);
    // Rename installer folder
    @rename(XOOPS_ROOT_PATH . "/install", XOOPS_ROOT_PATH . "/" . $installer_modified);
}

/**
 * xoFormField - display an input field
 *
 * @param string $name  field name
 * @param string $value value
 * @param string $label label
 * @param string $help  help text
 *
 * @return void
 */
function xoFormField($name, $value, $label, $help = '')
{
    $label = installHtmlSpecialCharacters($label);
    $name = installHtmlSpecialCharacters($name);
    $value = installHtmlSpecialCharacters($value);
    echo "<label class='xolabel' for='$name'>$label</label>\n";
    if ($help) {
        echo '<div class="xoform-help">' . $help . "</div>\n";
    }
    if ($name === "adminname") {
        echo "<input type='text' name='$name' id='$name' value='$value' maxlength='25' />";
    } else {
        echo "<input type='text' name='$name' id='$name' value='$value' />";
    }
}

/**
 * xoPassField - display a password field
 *
 * @param string $name  field name
 * @param string $value value
 * @param string $label label
 * @param string $help  help text
 *
 * @return void
 */
function xoPassField($name, $value, $label, $help = '')
{
    $label = installHtmlSpecialCharacters($label);
    $name = installHtmlSpecialCharacters($name);
    $value = installHtmlSpecialCharacters($value);
    echo "<label class='xolabel' for='{$name}'>{$label}</label>\n";
    if ($help) {
        echo '<div class="xoform-help">' . $help . "</div>\n";
    }

    if ($name === "adminpass") {
        echo "<input type='password' name='{$name}' id='{$name}' value='{$value}' onkeyup='passwordStrength(this.value)' />";
    } else {
        echo "<input type='password' name='{$name}' id='{$name}' value='{$value}' />";
    }
}

/**
 * xoBoolField - display a boolean checkbox field
 *
 * @param string $name  field name
 * @param string $value value
 * @param string $label label
 * @param string $help  help text
 *
 * @return void
 */
function xoBoolField($name, $value, $label, $help = '')
{
    $label = installHtmlSpecialCharacters($label);
    $name = installHtmlSpecialCharacters($name);
    $value = installHtmlSpecialCharacters($value);
    echo "<label class='xolabel' for='$name'>$label</label>\n";
    if ($help) {
        echo '<div class="xoform-help">' . $help . "</div>\n";
    }
    $checked = $value ? 'checked' : '';
    echo "<input type=\"checkbox\" name=\"{$name}\" value=\"1\" {$checked} />"
        . ENABLE . "<br />";
}

/*
 * gets list of name of directories inside a directory
 *
 * @param string $dirname
 * @return array
 */
function getDirList($dirname)
{
    $dirlist = array();
    if ($handle = opendir($dirname)) {
        while ($file = readdir($handle)) {
            if ($file[0] !== '.' && is_dir($dirname . $file)) {
                $dirlist[] = $file;
            }
        }
        closedir($handle);
        asort($dirlist);
        reset($dirlist);
    }
    return $dirlist;
}

/**
 * @param $status
 * @param string $str
 * @return string
 */
function xoDiag($status = -1, $str = '')
{
    if ($status == -1) {
        $_SESSION['error'] = true;
    }
    $classes = array(-1 => 'error', 0 => 'warning', 1 => 'success');
    $strings = array(-1 => FAILED, 0 => WARNING, 1 => SUCCESS);
    if (empty($str)) {
        $str = $strings[$status];
    }
    return '<span class="' . $classes[$status] . '">' . $str . '</span>';
}

/**
 * @param string $name
 * @param bool $wanted
 * @param bool $severe
 * @return string
 */
function xoDiagBoolSetting($name, $wanted = false, $severe = false)
{
    $setting = strtolower(ini_get($name));
    $setting = (empty($setting) || $setting === 'off' || $setting === 'false') ? false : true;
    if ($setting == $wanted) {
        return xoDiag(1, $setting ? 'ON' : 'OFF');
    } else {
        return xoDiag($severe ? -1 : 0, $setting ? 'ON' : 'OFF');
    }
}

/**
 * @param string $path
 * @return string
 */
function xoDiagIfWritable($path)
{
    $path = "../" . $path;
    $error = true;
    if (!is_dir($path)) {
        if (file_exists($path)) {
            @chmod($path, 0666);
            $error = !is_writeable($path);
        }
    } else {
        @chmod($path, 0777);
        $error = !is_writeable($path);
    }
    return xoDiag($error ? -1 : 1, $error ? 'Not writable' : 'Writable');
}

/**
 * @return string
 */
function xoPhpVersion()
{
    if (version_compare(phpversion(), '7.1.0', '>=')) {
        return xoDiag(1, phpversion());
    } else {
        return xoDiag(-1, phpversion());
    }
}

/**
 * @param string $path
 * @param bool $valid
 * @return string
 */
function genPathCheckHtml($path, $valid)
{
    if ($valid) {
        switch ($path) {
            case 'root':
                $msg = sprintf(XOOPS_FOUND, XOOPS_VERSION);
                break;

            case 'lib':
            case 'data':
            default:
                $msg = XOOPS_PATH_FOUND;
                break;
        }
        return '<span class="pathmessage"><img src="img/yes.png" alt="Success" />' . $msg . '</span>';
    } else {
        switch ($path) {
            case 'root':
                $msg = ERR_NO_XOOPS_FOUND;
                break;

            case 'lib':
            case 'data':
            default:
                $msg = ERR_COULD_NOT_ACCESS;
                break;
        }
        return '<span class="pathmessage"><img src="img/no.png" alt="Error" /> ' . $msg . '</span>';
    }
}

/**
 * getDbConnectionParams - build array of connection parameters from collected
 * DB_* session variables
 *
 * @return array of Doctrine Connection parameters
 */
function getDbConnectionParams()
{
    $wizard = $_SESSION['wizard'];
    $settings = $_SESSION['settings'];

    // get list of parameters the selected driver accepts
    $driver_info = $wizard->configs['db_types'][$settings['DB_DRIVER']];
    $driver_params=explode(',', $driver_info['params']);

    $connectionParams = array(
        'driver' => $settings['DB_DRIVER'],
        'charset' => 'utf8',
    );

    // force mysql to use utf8mb4
    if (false !== strstr($settings['DB_DRIVER'],'mysql')) {
        $connectionParams['charset'] = 'utf8mb4';
        $connectionParams['collate'] = 'utf8mb4_unicode_ci';
    }

    foreach ($driver_params as $param) {
        if (!empty($settings[$wizard->configs['db_param_names'][$param]])) {
            $connectionParams[$param] = $settings[$wizard->configs['db_param_names'][$param]];
        }
    }

    return $connectionParams;
}

/**
 * getDbConnection - get database connection based on current setting
 *
 * @param string &$error will be set with any error encountered
 *
 * @return Connection a database connection instance
 */
function getDbConnection(&$error)
{
    //New database connector
    $config = new \Doctrine\DBAL\Configuration();
    $connectionParams = getDbConnectionParams();

    try {
        $instance = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
    } catch (Exception $e) {
            $error = $e->getMessage();
            return false;
    }
    if (!$instance) {
        $error = ERR_NO_DBCONNECTION;
        return false;
    } else {
        try {
            // DBAL 4: connect() is protected; force connection with a lightweight query
            $instance->executeQuery('SELECT 1');
        } catch (Exception $e) {
            $error = $e->getMessage();
            return false;
        }
    }
    return $instance;
}
