<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

namespace Xoops\Auth;

/**
 * Authentication class factory
 *
 * @category  Xoops\Auth
 * @package   Factory
 * @author    Pierre-Eric MENUET <pemphp@free.fr>
 * @copyright 2000-2014 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      http://xoops.org
 * @since     2.0
 */
class Factory
{
    /**
     * Get a reference to the only instance of authentication class
     *
     * if the class has not been instantiated yet, this will also take
     * care of that
     *
     * @param string $uname user name
     * @param bool $_force internal use for tests
     *
     * @return AuthAbstract|bool Reference to the only instance of authentication class
     */
    public static function getAuthConnection($uname, $_force=false)
    {
        $xoops = \Xoops::getInstance();
        static $auth_instance;
        if (!isset($auth_instance) || (bool)$_force) {
            /* @var $config_handler XoopsConfigHandler */
            $authConfig = $xoops->getConfigs();
            if (empty($authConfig['auth_method'])) { // If there is a config error, we use xoops
                $xoops_auth_method = 'xoops';
            } else {
                $xoops_auth_method = $authConfig['auth_method'];
            }
            // Verify if uname allow to bypass LDAP auth
            if ($xoops_auth_method !== 'xoops' && self::isBypassed($xoops_auth_method, (string) $uname, $authConfig)) {
                $xoops_auth_method = 'xoops';
            }

            $class = '\Xoops\Auth\\' . ucfirst($xoops_auth_method);
            if (!class_exists($class)) {
                trigger_error(\XoopsLocale::EF_CLASS_NOT_FOUND, E_USER_ERROR);
                return false;
            }
            $dao = null;
            switch ($xoops_auth_method) {
                case 'xoops':
                    $dao = null;
                    break;
                case 'ldap':
                    $dao = null;
                    break;
                case 'ads':
                    $dao = null;
                    break;
            }
            $auth_instance = new $class($dao);
        }
        return $auth_instance;
    }

    /**
     * Whether the account a directory login would resolve to is reserved for native login.
     *
     * Compares the name the backend looks up locally: AD drops a DOMAIN\ prefix, and the
     * local user lookup uses the database collation, so names it treats as equal also match.
     *
     * @param string $method     configured directory backend (ldap or ads)
     * @param string $uname      submitted user name
     * @param array  $authConfig site configuration
     *
     * @return bool
     */
    private static function isBypassed($method, $uname, array $authConfig)
    {
        $bypassList = array_filter(array_map(
            'mb_strtolower',
            array_map('strval', (array) ($authConfig['ldap_users_bypass'] ?? []))
        ), 'strlen');
        if ($bypassList === [] || $uname === '') {
            return false;
        }
        if ($method === 'ads') {
            $parts = explode('\\', $uname);
            $uname = (string) end($parts);
        }
        if (in_array(mb_strtolower($uname), $bypassList, true)) {
            return true;
        }
        // a spelling the collation folds onto a reserved account (admín -> admin) is that account
        try {
            $users = \Xoops::getInstance()->getHandlerMember()->getUsers(new \Xoops\Core\Kernel\Criteria('uname', $uname));
        } catch (\Doctrine\DBAL\Exception $e) {
            // no database: there is no local account to protect, and the login cannot complete anyway
            return false;
        }
        foreach ($users as $user) {
            if (in_array(mb_strtolower((string) $user->getVar('uname', 'n')), $bypassList, true)) {
                return true;
            }
        }
        return false;
    }
}
