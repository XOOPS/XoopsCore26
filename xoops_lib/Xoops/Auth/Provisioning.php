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

use Xoops\Core\Kernel\Criteria;
use Xoops\Core\Kernel\Handlers\XoopsUser;

/**
 * Authentication provisioning class
 *
 * This class is responsible to provide synchronisation method to Xoops User Database
 *
 * @category  Xoops\Auth
 * @package   Provisioning
 * @author    Pierre-Eric MENUET <pemphp@free.fr>
 * @copyright 2000-2015 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      http://xoops.org
 * @since     2.0
 */
class Provisioning
{
    /**
     * @var AuthAbstract instance
     */
    protected $auth_instance;

    /**
     * @var bool
     */
    public $ldap_provisioning;

    /**
     * @var bool
     */
    public $ldap_provisioning_upd;

    /**
     * var array
     */
    public $ldap_field_mapping;

    /**
     * @var array
     */
    public $ldap_provisioning_group;

    /**
     * getInstance()
     *
     * @param AuthAbstract $auth_instance auth instance
     *
     * @return Provisioning Xoops\Auth\Provisioning
     */
    public static function getInstance(AuthAbstract $auth_instance)
    {
        static $provis_instance;
        if (!isset($provis_instance)) {
            $provis_instance = new self($auth_instance);
        }

        return $provis_instance;
    }

    /**
     * Authentication Service constructor
     *
     * @param AuthAbstract $auth_instance auth instance
     */
    public function __construct(AuthAbstract $auth_instance)
    {
        $xoops = \Xoops::getInstance();
        $this->auth_instance = $auth_instance;
        $configs = $xoops->getConfigs();
        foreach ($configs as $key => $val) {
            $this->$key = $val;
        }
    }

    /**
     * Return a Xoops User Object
     *
     * @param string $uname username
     *
     * @return mixed bool|XoopsUser
     */
    public function getXoopsUser($uname)
    {
        $xoops = \Xoops::getInstance();
        $member_handler = $xoops->getHandlerMember();
        $criteria = new Criteria('uname', $uname);
        $getuser = $member_handler->getUsers($criteria);
        if (count($getuser) == 1) {
            return $getuser[0];
        } else {
            return false;
        }
    }

    /**
     * Launch the synchronisation process
     *
     * @param string $data  data
     * @param string $uname username
     * @param string $pwd   password
     *
     * @return bool|XoopsUser
     */
    public function sync($data, $uname, $pwd = null)
    {
        $xoopsUser = $this->getXoopsUser($uname);
        if (!$xoopsUser) { // Xoops User Database not exists
            if ($this->ldap_provisioning) {
                $xoopsUser = $this->add($data, $uname, $pwd);
            } else {
                $this->auth_instance->setErrors(0, sprintf(
                    \XoopsLocale::EF_CORRESPONDING_USER_NOT_FOUND_IN_DATABASE,
                    $uname
                ));
            }
        } else { // Xoops User Database exists
            if ($this->ldap_provisioning && $this->ldap_provisioning_upd) {
                $xoopsUser = $this->change($xoopsUser, $data, $uname, $pwd);
            }
        }

        return $xoopsUser;
    }

    /**
     * setVarsMapping
     *
     * @param object $object user object
     * @param array  $data   data
     *
     * @return void
     */
    protected function setVarsMapping($object, $data)
    {
        $tab_mapping = explode('|', $this->ldap_field_mapping);
        foreach ($tab_mapping as $mapping) {
            $fields = explode('=', trim($mapping));
            if (isset($fields[0]) && ($field0 = trim($fields[0]))) {
                $str = '';
                if (isset($fields[1]) && ($field1 = trim($fields[1]))) {
                    if (!empty($data[$field1][0])) {
                        $str = $data[$field1][0];
                    }
                }
                $object->setVar($field0, $str);
            }
        }
    }

    /**
     * Add a new user to the system
     *
     * @param string $data  data
     * @param string $uname username
     * @param string $pwd   password
     *
     * @return mixed XoopsUser or false
     */
    public function add($data, $uname, $pwd = null)
    {
        $xoops = \Xoops::getInstance();
        $ret = false;
        $member_handler = $xoops->getHandlerMember();
        // Create XOOPS Database User
        $newuser = $member_handler->createUser();
        $newuser->setVar('uname', $uname);
        $newuser->setVar('pass', password_hash(stripslashes($pwd), PASSWORD_DEFAULT));
        $newuser->setVar('last_pass_change', time());
        $newuser->setVar('rank', 0);
        $newuser->setVar('level', 1);
        $newuser->setVar('timezone', $xoops->getConfig('default_TZ'));
        $newuser->setVar('theme', $xoops->getConfig('theme_set'));
        //$newuser->setVar('umode', $xoops->getConfig('com_mode'));
        //$newuser->setVar('uorder', $xoops->getConfig('com_order'));
        $newuser->setVar('user_regdate', time());
        $this->setVarsMapping($newuser, $data);

        if ($member_handler->insertUser($newuser)) {
            foreach ($this->ldap_provisioning_group as $groupid) {
                $member_handler->addUserToGroup($groupid, $newuser->getVar('uid'));
            }
            $newuser->unsetNew();

            return $newuser;
        } else {
            $xoops->redirect(\XoopsBaseConfig::get('url') . '/user.php', 5, $newuser->getHtmlErrors());
        }

        return $ret;
    }

    /**
     * Modify user information
     *
     * @param XoopsUser $xoopsUser user object
     * @param string    $data      data
     * @param string    $uname     username
     * @param string    $pwd       password
     *
     * @return bool|XoopsUser
     */
    public function change(XoopsUser $xoopsUser, $data, $uname, $pwd = null)
    {
        $xoops = \Xoops::getInstance();
        $ret = false;
        $member_handler = $xoops->getHandlerMember();
        // this runs on every synchronised login: only a changed directory password is a password change
        $passwordChanged = !$member_handler->verifyPassword($xoopsUser, stripslashes($pwd));
        if ($passwordChanged) {
            $xoopsUser->setVar('pass', password_hash(stripslashes($pwd), PASSWORD_DEFAULT));
            $xoopsUser->setVar('last_pass_change', time());
        }
        $this->setVarsMapping($xoopsUser, $data);

        if ($member_handler->insertUser($xoopsUser)) {
            if ($passwordChanged) {
                (new \Xoops\Core\Session\RememberMe())->invalidateAllForUser($xoopsUser->getVar('uid'));
            }
            return $xoopsUser;
        } else {
            $xoops->redirect(\XoopsBaseConfig::get('url') . '/user.php', 5, $xoopsUser->getHtmlErrors());
        }

        return $ret;
    }

    /**
     * Modify a user
     *
     * @return boolean|null
     */
    public function delete()
    {
    }

    /**
     * Suspend a user
     *
     * @return boolean|null
     */
    public function suspend()
    {
    }

    /**
     * Restore a user
     *
     * @return boolean|null
     */
    public function restore()
    {
    }

    /**
     * Add a new user to the system
     *
     * @return boolean|null
     */
    public function resetpwd()
    {
    }
}
