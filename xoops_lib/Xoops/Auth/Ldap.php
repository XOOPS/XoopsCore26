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

use Xoops\Core\Database\Connection;

/**
 * Authentication class for standard LDAP Server V3
 *
 * @category  Xoops\Auth
 * @package   Ldap
 * @author    Pierre-Eric MENUET <pemphp@free.fr>
 * @copyright 2000-2014 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      http://xoops.org
 * @since     2.0
 */
class Ldap extends AuthAbstract
{

    /**
     * @var
     */
    public $ldap_server;

    /**
     * @var string
     */

    public $ldap_port = '389';
    /**
     * @var string
     */
    public $ldap_version = '3';

    /**
     * @var
     */
    public $ldap_base_dn;

    /**
     * @var
     */
    public $ldap_loginname_asdn;

    /**
     * @var
     */
    public $ldap_loginldap_attr;

    /**
     * @var
     */
    public $ldap_mail_attr;

    /**
     * @var
     */
    public $ldap_name_attr;

    /**
     * @var
     */
    public $ldap_surname_attr;

    /**
     * @var
     */
    public $ldap_givenname_attr;

    /**
     * @var
     */
    public $ldap_manager_dn;

    /**
     * @var
     */
    public $ldap_manager_pass;

    /**
     * @var
     */
    public $ds;

    /**
     * @var
     */
    public $ldap_use_TLS;

    /**
     * @var
     */
    public $ldap_domain_name;

    /**
     * @var
     */
    public $ldap_filter_person;

    /**
     * Authentication Service constructor
     *
     * @param Connection|null $dao databse
     */
    public function __construct(?Connection $dao = null)
    {
        if (!extension_loaded('ldap')) {
            trigger_error(sprintf(\XoopsLocale::F_EXTENSION_PHP_NOT_LOADED, 'LDAP'), E_USER_ERROR);
            return;
        }

        $xoops = \Xoops::getInstance();
        $this->dao = $dao;
        //Configuration options that are stored in the database
        $configs = $xoops->getConfigs();
        foreach ($configs as $key => $val) {
            $this->$key = $val;
        }
    }

    /**
     * Authenticate  user again LDAP directory (Bind)
     *               2 options :
     *         Authenticate directly with uname in the DN
     *         Authenticate with manager, search the dn
     *
     * @param string $uname Username
     * @param string $pwd   Password
     *
     * @return bool
     */
    public function authenticate($uname, $pwd = null)
    {
        $authenticated = false;
        $this->ds = ldap_connect($this->ldap_server, $this->ldap_port);
        if ($this->ds) {
            ldap_set_option($this->ds, LDAP_OPT_PROTOCOL_VERSION, $this->ldap_version);
            if ($this->ldap_use_TLS) { // We use TLS secure connection
                if (!ldap_start_tls($this->ds)) {
                    $this->setErrors(0, \XoopsLocale::E_TLS_CONNECTION_NOT_OPENED);
                }
            }
            // If the uid is not in the DN we proceed to a search
            // The uid is not always in the dn
            $userDN = $this->getUserDN($uname);
            if (!(is_string($userDN))) {
                return false;
            }
            // We bind as user to test the credentials
            $authenticated = ldap_bind($this->ds, $userDN, stripslashes($pwd));
            if ($authenticated) {
                // We load the Xoops User database
                return $this->loadXoopsUser($userDN, $uname, $pwd);
            } else {
                $this->setErrors(ldap_errno($this->ds), ldap_err2str(ldap_errno($this->ds)) . '(' . $userDN . ')');
            }
        } else {
            $this->setErrors(0, \XoopsLocale::E_CANNOT_CONNECT_TO_SERVER);
        }
        @ldap_close($this->ds);

        return $authenticated;
    }

    /**
     * Compose the user DN with the configuration.
     *
     * @param string $uname username
     *
     * @return bool|string userDN or false
     */
    public function getUserDN($uname)
    {
        $userDN = false;
        if (!$this->ldap_loginname_asdn) {
            // Bind with the manager
            if (!ldap_bind($this->ds, $this->ldap_manager_dn, stripslashes($this->ldap_manager_pass))) {
                $this->setErrors(
                    ldap_errno($this->ds),
                    ldap_err2str(ldap_errno($this->ds)) . '(' . $this->ldap_manager_dn . ')'
                );

                return false;
            }
            $filter = $this->getFilter($uname);
            $sr = ldap_search($this->ds, $this->ldap_base_dn, $filter);
            $info = ldap_get_entries($this->ds, $sr);
            if ($info['count'] > 0) {
                $userDN = $info[0]['dn'];
            } else {
                $this->setErrors(0, sprintf(
                    \XoopsLocale::EF_USER_NOT_FOUND_IN_DIRECTORY_SERVER,
                    $uname,
                    $filter,
                    $this->ldap_base_dn
                ));
            }
        } else {
            $userDN = $this->ldap_loginldap_attr . '=' . $uname . ',' . $this->ldap_base_dn;
        }

        return $userDN;
    }

    /**
     * Load user from XOOPS Database
     *
     * @param string $uname username
     *
     * @return mixed|string
     */
    public function getFilter($uname)
    {
        if ($this->ldap_filter_person != '') {
            $filter = str_replace('@@loginname@@', $uname, $this->ldap_filter_person);
        } else {
            $filter = $this->ldap_loginldap_attr . '=' . $uname;
        }

        return $filter;
    }

    /**
     * loadXoopsUser
     *
     * @param string $userdn base DN for the directory
     * @param string $uname  username
     * @param string $pwd    pasword
     *
     * @return bool|XoopsUser
     */
    public function loadXoopsUser($userdn, $uname, $pwd = null)
    {
        $xoopsUser = false;
        $provisHandler = Provisioning::getInstance($this);
        $sr = ldap_read($this->ds, $userdn, '(objectclass=*)');
        $entries = ldap_get_entries($this->ds, $sr);
        if ($entries['count'] > 0) {
            $xoopsUser = $provisHandler->sync($entries[0], $uname, $pwd);
        } else {
            $this->setErrors(0, sprintf('loadXoopsUser - ' . \XoopsLocale::EF_ENTRY_NOT_READ, $userdn));
        }

        return $xoopsUser;
    }
}
