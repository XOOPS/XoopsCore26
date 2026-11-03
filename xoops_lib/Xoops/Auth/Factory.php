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
            // compare the account name the directory backends resolve: AD drops a DOMAIN\ prefix,
            // and the local user lookup ignores case
            $parts = explode('\\', (string) $uname);
            $bypassName = strtolower((string) end($parts));
            $bypassList = array_map('strtolower', array_map('strval', (array) ($authConfig['ldap_users_bypass'] ?? [])));
            if (in_array($bypassName, $bypassList, true)) {
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
}
