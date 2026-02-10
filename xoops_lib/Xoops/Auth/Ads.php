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
 * Authentication class for Active Directory
 *
 * @category  Xoops\Auth
 * @package   Ldap
 * @author    Pierre-Eric MENUET <pemphp@free.fr>
 * @copyright 2000-2014 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      http://xoops.org
 * @since     2.0
 */
class Ads extends Ldap
{
    /**
     * Authentication Service constructor
     *
     * @param Connection|null $dao database
     *
     * @return void
     */
    public function __construct(?Connection $dao = null)
    {
        parent::__construct($dao);
    }

    /**
     * Authenticate  user again LDAP directory (Bind)
     *         2 options :
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
        if (!extension_loaded('ldap')) {
            $this->setErrors(0, \XoopsLocale::E_EXTENSION_PHP_LDAP_NOT_LOADED);
            return $authenticated;
        }
        $this->ds = ldap_connect($this->ldap_server, $this->ldap_port);
        if ($this->ds) {
            ldap_set_option($this->ds, LDAP_OPT_PROTOCOL_VERSION, $this->ldap_version);
            ldap_set_option($this->ds, LDAP_OPT_REFERRALS, 0);
            if ($this->ldap_use_TLS) { // We use TLS secure connection
                if (!ldap_start_tls($this->ds)) {
                    $this->setErrors(0, \XoopsLocale::E_TLS_CONNECTION_NOT_OPENED);
                }
            }
            // remove the domain name prefix from the username
            $uname = explode("\\", $uname);
            $uname = (sizeof($uname) > 0) ? $uname[sizeof($uname) - 1] : $uname = $uname[0];
            // If the uid is not in the DN we proceed to a search
            // The uid is not always in the dn
            $userUPN = $this->getUPN($uname);
            if (!$userUPN) {
                return false;
            }
            // We bind as user to test the credentials
            $authenticated = ldap_bind($this->ds, $userUPN, stripslashes($pwd));
            if ($authenticated) {
                // We load the Xoops User database
                $dn = $this->getUserDN($uname);
                if ($dn) {
                    return $this->loadXoopsUser($dn, $uname, $pwd);
                } else {
                    return false;
                }
            } else {
                $this->setErrors(ldap_errno($this->ds), ldap_err2str(ldap_errno($this->ds)) . '(' . $userUPN . ')');
            }
        } else {
            $this->setErrors(0, \XoopsLocale::E_CANNOT_CONNECT_TO_SERVER);
        }
        @ldap_close($this->ds);
        return $authenticated;
    }

    /**
     * Return the UPN = userPrincipalName (Active Directory)
     *         userPrincipalName = guyt@CP.com    Often abbreviated to UPN, and
     *         looks like an email address.  Very useful for logging on especially in
     *         a large Forest.   Note UPN must be unique in the forest.
     *
     * @param string $uname username
     *
     * @return string userDN
     */
    public function getUPN($uname)
    {
        $userDN = $uname . '@' . $this->ldap_domain_name;
        return $userDN;
    }
}
