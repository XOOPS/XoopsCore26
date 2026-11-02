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
 * avatars module
 *
 * @copyright       XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         avatar
 * @since           2.6.0
 * @author          Mage Grégory (AKA Mage)
 * @version         $Id$
 */
require_once dirname(dirname(dirname(__DIR__))) . '/include/cp_header.php';

/**
 * AJAX toggle of an avatar's display flag, shared by the system and custom avatar pages
 *
 * @param \Xoops\Core\Kernel\XoopsPersistableObjectHandler $avatarHandler avatar handler
 *
 * @return void
 */
function avatars_updateDisplay(\Xoops\Core\Kernel\XoopsPersistableObjectHandler $avatarHandler): void
{
    \Xoops::getInstance()->security()->requireAjaxToken();
    $avatar_id = \Xmf\Request::getInt('avatar_id', 0, 'POST');
    $obj = $avatar_id > 0 ? $avatarHandler->get($avatar_id) : null;
    if (is_object($obj)) {
        $obj->setVar('avatar_display', !$obj->getVar('avatar_display'));
        if ($avatarHandler->insert($obj)) {
            exit;
        }
        echo $obj->getHtmlErrors();
    }
}
