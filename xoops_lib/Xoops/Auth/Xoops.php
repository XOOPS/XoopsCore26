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
 * Authentication class for Native XOOPS
 *
 * @category  Xoops\Auth
 * @package   Xoops
 * @author    Pierre-Eric MENUET <pemphp@free.fr>
 * @copyright 2000-2014 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      http://xoops.org
 * @since     2.0
 */
class Xoops extends AuthAbstract
{
    /**
     * Authentication Service constructor
     *
     * @param Connection|null $dao database object
     */
    public function __construct(?Connection $dao = null)
    {
        $this->dao = $dao;
        $this->auth_method = 'xoops';
    }

    /**
     * Authenticate user
     *
     * @param string $uname user name
     * @param string $pwd   password
     *
     * @return bool
     */
    public function authenticate($uname, $pwd = null)
    {
        $xoops = \Xoops::getInstance();
        $member_handler = $xoops->getHandlerMember();
        $user = false;
        if ($member_handler) {
            $user = $member_handler->loginUser($uname, $pwd);
            if ($user == false) {
                $this->setErrors(1, \XoopsLocale::E_INCORRECT_LOGIN);
            }
        }

        return $user;
    }
}
